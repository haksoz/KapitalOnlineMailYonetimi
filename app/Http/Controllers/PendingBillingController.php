<?php

namespace App\Http\Controllers;

use App\Models\Cari;
use App\Models\ExchangeRate;
use App\Models\PendingBilling;
use App\Models\Subscription;
use App\Models\SubscriptionQuantityChange;
use App\Services\ArenaXmlParser;
use App\Services\PendingBillingService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class PendingBillingController extends Controller
{
    public function index(Request $request): View
    {
        $status = $request->get('status', PendingBilling::STATUS_PENDING);
        if (in_array($status, [PendingBilling::STATUS_PENDING, PendingBilling::STATUS_POSTPONED, PendingBilling::STATUS_INVOICED, PendingBilling::STATUS_CANCELLED], true)) {
            // ok
        } else {
            $status = PendingBilling::STATUS_PENDING;
        }

        $perPage = (int) $request->get('per_page', 20);
        if (! in_array($perPage, [15, 20, 25, 50, 100], true)) {
            $perPage = 20;
        }

        $query = PendingBilling::query()
            ->with(['subscription.customerCari', 'subscription.product', 'salesInvoiceLine'])
            ->where('status', $status);

        if ($request->filled('customer_cari_id')) {
            $query->whereHas('subscription', fn ($q) => $q->where('customer_cari_id', (int) $request->customer_cari_id));
        }
        if ($request->filled('period_year')) {
            $query->whereYear('period_start', (int) $request->period_year);
        }
        if ($request->filled('period_month')) {
            $query->whereMonth('period_start', (int) $request->period_month);
        }

        // Alış faturasının varlığı/yokluğu filtresi
        $hasSupplier = $request->get('has_supplier_invoice');
        if ($hasSupplier === 'with') {
            $query->where(function ($q) {
                $q->whereNotNull('supplier_invoice_number')
                    ->orWhereNotNull('supplier_invoice_date')
                    ->orWhereNotNull('actual_alis_tl');
            });
        } elseif ($hasSupplier === 'without') {
            $query->whereNull('supplier_invoice_number')
                ->whereNull('supplier_invoice_date')
                ->whereNull('actual_alis_tl');
        }

        // Tüm sekmelerde (beklemede, faturalandı, iptal) ID'ye göre, büyükten küçüğe sıralama
        $query->orderByDesc('id');

        $pendingBillings = $query->paginate($perPage)->withQueryString();

        $caris = Cari::whereIn('cari_type', ['customer', 'both'])
            ->orderBy('name')
            ->get(['id', 'name', 'short_name']);

        // Faturalandı listesinde kesinleşen satışı fatura satırı ile senkronize et (fark eklendi ama actual_satis_tl güncellenmemiş olabilir)
        if ($status === PendingBilling::STATUS_INVOICED) {
            foreach ($pendingBillings as $pb) {
                $line = $pb->salesInvoiceLine;
                if ($line !== null) {
                    $lineAmount = (float) $line->line_amount_tl;
                    $current = $pb->actual_satis_tl !== null && $pb->actual_satis_tl !== '' ? (float) $pb->actual_satis_tl : null;
                    if ($current === null || abs($current - $lineAmount) > 0.001) {
                        $pb->update(['actual_satis_tl' => $lineAmount]);
                    }
                }
            }
        }

        // Abonelik bazında birikmiş fark (önceki faturalandırılmış kayıtlardaki fee_difference_tl toplamı)
        $subscriptionIds = $pendingBillings->pluck('subscription_id')->unique()->filter()->values()->all();
        $accumulatedFarkBySubscription = [];
        if ($subscriptionIds !== []) {
            $totals = PendingBilling::query()
                ->where('status', PendingBilling::STATUS_INVOICED)
                ->whereNotNull('fee_difference_tl')
                ->whereIn('subscription_id', $subscriptionIds)
                ->selectRaw('subscription_id, SUM(fee_difference_tl) as total')
                ->groupBy('subscription_id')
                ->get();
            foreach ($totals as $row) {
                $accumulatedFarkBySubscription[$row->subscription_id] = (float) $row->total;
            }
        }

        // Beklenen alış/satış için kur: önce bugünkü USD, yoksa son bilinen USD efektif satış
        $usdToday = ExchangeRate::where('currency_code', 'USD')
            ->where('effective_date', now()->toDateString())
            ->first();
        if ($usdToday?->forex_selling !== null && $usdToday->forex_selling !== '') {
            $usdEfektifSelling = (float) $usdToday->forex_selling;
        } else {
            $usdLast = ExchangeRate::where('currency_code', 'USD')
                ->whereNotNull('forex_selling')
                ->orderByDesc('effective_date')
                ->first();
            $usdEfektifSelling = $usdLast?->forex_selling !== null && $usdLast->forex_selling !== '' ? (float) $usdLast->forex_selling : null;
        }

        return view('pending-billings.index', [
            'pendingBillings' => $pendingBillings,
            'usdEfektifSelling' => $usdEfektifSelling,
            'currentStatus' => $status,
            'accumulatedFarkBySubscription' => $accumulatedFarkBySubscription,
            'caris' => $caris,
        ]);
    }

    public function refreshAmounts(PendingBilling $pending_billing, PendingBillingService $pendingBillingService): RedirectResponse
    {
        $updated = $pendingBillingService->refreshAmountsForRecord($pending_billing);

        if ($updated) {
            return redirect()->route('pending-billings.index', request()->only('status'))
                ->with('success', 'Beklenen alış ve satış tutarları güncel kur ile hesaplanıp veritabanına kaydedildi. Faturalandırma yaparken bu kayıtlı tutarlar kullanılır.');
        }

        return redirect()->route('pending-billings.index', request()->only('status'))
            ->with('error', 'Kayıt yapılamadı. Kayıt beklemede değilse, USD kuru tanımlı değilse veya abonelikte birim alış (USD) yoksa tutarlar hesaplanamaz.');
    }

    public function showSupplierInvoice(PendingBilling $pending_billing): View|RedirectResponse
    {
        if ($pending_billing->actual_alis_tl !== null && $pending_billing->actual_alis_tl !== '') {
            return redirect()
                ->route('pending-billings.index', ['status' => request('status', 'pending')])
                ->with('error', 'Alış faturası zaten girilmiş. Düzeltme şu an kapalı; ileride ayrıntı sayfasından yönetilecek.');
        }

        $pending_billing->load('subscription');

        return view('pending-billings.supplier-invoice', [
            'pendingBilling' => $pending_billing,
        ]);
    }

    public function storeSupplierInvoice(Request $request, PendingBilling $pending_billing): RedirectResponse
    {
        if ($pending_billing->actual_alis_tl !== null && $pending_billing->actual_alis_tl !== '') {
            return redirect()
                ->route('pending-billings.index', ['status' => $request->get('status', 'pending')])
                ->with('error', 'Alış faturası zaten girilmiş. Düzeltme şu an kapalı.');
        }

        $validated = $request->validate([
            'supplier_invoice_number' => ['required', 'string', 'max:64'],
            'supplier_invoice_date' => ['required', 'date'],
            'quantity' => ['required', 'integer', 'min:1'],
            'actual_alis_tl' => ['required', 'numeric', 'min:0'],
        ]);

        $subscription = $pending_billing->subscription;
        $effectiveDate = $validated['supplier_invoice_date'];
        $newQuantity = (int) $validated['quantity'];
        $previousQuantity = (int) $subscription->quantity;

        $pending_billing->update([
            'supplier_invoice_number' => $validated['supplier_invoice_number'],
            'supplier_invoice_date' => $effectiveDate,
            'actual_alis_tl' => $validated['actual_alis_tl'],
        ]);

        $satisFromAlis = null;
        if ((float) $subscription->usd_birim_alis > 0 && $subscription->usd_birim_satis !== null) {
            $satisFromAlis = (float) $validated['actual_alis_tl'] * ((float) $subscription->usd_birim_satis / (float) $subscription->usd_birim_alis);
        }

        $line = $pending_billing->salesInvoiceLine;
        if ($line !== null) {
            // Zaten faturalandı: beklenen satışa yazılır. Fark = beklenen − kesinleşen (pozitif = zarar; sonraki ayda "Farkı ekle" ile telafi edilir).
            $pending_billing->update(['expected_satis_tl' => $satisFromAlis]);
            $feeDifferenceTl = null;
            if ($satisFromAlis !== null && $line->line_amount_tl !== null && $line->line_amount_tl !== '') {
                $feeDifferenceTl = (float) $satisFromAlis - (float) $line->line_amount_tl;
            }
            $pending_billing->update(['fee_difference_tl' => $feeDifferenceTl]);
        } else {
            // Henüz beklemede: gerçek alıştan hesaplanan tutar beklenen satış olarak güncellenir, gerçek satış yazılmaz
            $pending_billing->update(['expected_satis_tl' => $satisFromAlis]);
        }

        if ($newQuantity !== $previousQuantity) {
            SubscriptionQuantityChange::create([
                'subscription_id' => $subscription->id,
                'previous_quantity' => $previousQuantity,
                'new_quantity' => $newQuantity,
                'effective_date' => $effectiveDate,
            ]);
            $subscription->update(['quantity' => $newQuantity]);
        }

        $backStatus = $request->get('status', 'pending');

        return redirect()
            ->route('pending-billings.index', ['status' => $backStatus])
            ->with('success', 'Alış faturası kaydedildi.' . ($newQuantity !== $previousQuantity ? ' Abonelik adeti güncellendi.' : ''));
    }

    public function clearSupplierInvoice(Request $request, PendingBilling $pending_billing): RedirectResponse
    {
        $pending_billing->update([
            'supplier_invoice_number' => null,
            'supplier_invoice_date' => null,
            'actual_alis_tl' => null,
            'expected_satis_tl' => null,
            'fee_difference_tl' => null,
        ]);

        $backStatus = $request->get('status', $pending_billing->status ?? PendingBilling::STATUS_PENDING);

        return redirect()
            ->route('pending-billings.index', ['status' => $backStatus])
            ->with('success', 'Alış faturası bu siparişten kaldırıldı. Gerekirse doğru dönem için tekrar alış faturası girebilirsiniz.');
    }

    public function postpone(Request $request, PendingBilling $pending_billing): RedirectResponse
    {
        if ($pending_billing->status !== PendingBilling::STATUS_PENDING) {
            return redirect()
                ->route('pending-billings.index', ['status' => $request->get('status', PendingBilling::STATUS_PENDING)])
                ->with('error', 'Sadece beklemede olan siparişler ertelenebilir.');
        }

        $pending_billing->update(['status' => PendingBilling::STATUS_POSTPONED]);

        return redirect()
            ->route('pending-billings.index', ['status' => $request->get('status', PendingBilling::STATUS_PENDING)])
            ->with('success', 'Sipariş ertelendi. İleride Ertelendi sekmesinden faturalandırabilirsiniz.');
    }

    public function showSupplierInvoiceXml(Request $request): View
    {
        $supplierCaris = Cari::whereIn('cari_type', ['supplier', 'both'])
            ->orderBy('name')
            ->get(['id', 'name', 'short_name']);

        $currentStatus = $request->get('status', PendingBilling::STATUS_PENDING);

        return view('pending-billings.supplier-invoice-xml', [
            'supplierCaris' => $supplierCaris,
            'currentStatus' => $currentStatus,
        ]);
    }

    public function storeSupplierInvoiceXml(Request $request, ArenaXmlParser $parser): RedirectResponse
    {
        $validated = $request->validate([
            'provider_cari_id' => ['required', 'integer', 'exists:caris,id'],
            'xml_file' => ['nullable', 'file', 'max:10240', 'required_without:xml_content'],
            'xml_content' => ['nullable', 'string', 'required_without:xml_file'],
        ]);

        $cari = Cari::find($validated['provider_cari_id']);
        if (! $cari || ! in_array($cari->cari_type, ['supplier', 'both'], true)) {
            return redirect()
                ->route('pending-billings.supplier-invoice-xml', ['status' => $request->get('status', 'pending')])
                ->withInput()
                ->with('error', 'Seçilen cari tedarikçi değil.');
        }

        $xmlContent = null;
        if (! empty($validated['xml_content'])) {
            $xmlContent = (string) $validated['xml_content'];
        } elseif ($request->hasFile('xml_file')) {
            $xmlContent = file_get_contents($validated['xml_file']->getRealPath());
        }

        if ($xmlContent === null || trim($xmlContent) === '') {
            return redirect()
                ->route('pending-billings.supplier-invoice-xml', ['status' => $request->get('status', 'pending')])
                ->withInput()
                ->with('error', 'XML içeriği bulunamadı. Dosya seçin veya XML metnini yapıştırın.');
        }

        try {
            $parsed = $parser->parse($xmlContent);
        } catch (\Throwable $e) {
            return redirect()
                ->route('pending-billings.supplier-invoice-xml', ['status' => $request->get('status', 'pending')])
                ->withInput()
                ->with('error', 'XML okunamadı. Lütfen geçerli bir UBL alış faturası XML\'i yapıştırın veya dosya seçin. Hata: ' . $e->getMessage());
        }

        $sellerVkn = $parsed['seller_vkn'] !== null ? preg_replace('/\s+/', '', $parsed['seller_vkn']) : '';
        $cariTax = $cari->tax_number !== null && $cari->tax_number !== '' ? preg_replace('/\s+/', '', $cari->tax_number) : '';
        if ($sellerVkn !== '' && $cariTax !== '' && $sellerVkn !== $cariTax) {
            return redirect()
                ->route('pending-billings.supplier-invoice-xml', ['status' => $request->get('status', 'pending')])
                ->withInput()
                ->with('error', 'Fatura vergi numarası (' . $parsed['seller_vkn'] . ') seçilen tedarikçi ile uyuşmuyor.');
        }

        $request->session()->put('supplier_invoice_xml_parsed', [
            'provider_cari_id' => (int) $validated['provider_cari_id'],
            'provider_cari_name' => $cari->short_name ?: $cari->name,
            'back_status' => $request->get('status', 'pending'),
            'invoice_no' => $parsed['invoice_no'],
            'issue_date' => $parsed['issue_date'],
            'seller_vkn' => $parsed['seller_vkn'],
            'lines' => $parsed['lines'],
        ]);

        return redirect()->route('pending-billings.supplier-invoice-xml-preview');
    }

    public function showSupplierInvoiceXmlPreview(Request $request): View|RedirectResponse
    {
        $data = $request->session()->get('supplier_invoice_xml_parsed');
        if (! is_array($data)) {
            return redirect()->route('pending-billings.index')->with('error', 'Önizleme verisi bulunamadı. XML’i tekrar yükleyin.');
        }

        $providerCariId = (int) ($data['provider_cari_id'] ?? 0);
        $lines = $data['lines'] ?? [];
        $issueDateStr = $data['issue_date'] ?? '';

        $defaultPeriodYear = null;
        $defaultPeriodMonth = null;
        if ($issueDateStr !== '') {
            $dt = Carbon::parse($issueDateStr);
            $defaultPeriodYear = (int) $dt->year;
            $defaultPeriodMonth = (int) $dt->month;
        }

        $linePeriods = [];
        $lineRecentBillings = [];
        $lineCustomerNames = [];
        foreach ($lines as $index => $line) {
            $sozlesmeNo = trim((string) ($line['sozlesme_no'] ?? ''));
            $periods = [];
            $recentBillings = [];
            if ($sozlesmeNo !== '') {
                $subscription = Subscription::query()
                    ->where('provider_cari_id', $providerCariId)
                    ->where('sozlesme_no', $sozlesmeNo)
                    ->with('customerCari')
                    ->first();
                if ($subscription) {
                    $starts = PendingBilling::query()
                        ->where('subscription_id', $subscription->id)
                        ->orderBy('period_start')
                        ->get(['period_start'])
                        ->pluck('period_start')
                        ->unique()
                        ->values();
                    foreach ($starts as $ps) {
                        if ($ps) {
                            $periods[] = [
                                'year' => (int) $ps->year,
                                'month' => (int) $ps->month,
                                'label' => $ps->locale('tr')->translatedFormat('F Y'),
                            ];
                        }
                    }

                    // İlişkili son 3 sipariş (en güncel dönem önce)
                    $recent = PendingBilling::query()
                        ->where('subscription_id', $subscription->id)
                        ->with('salesInvoiceLine')
                        ->orderByDesc('period_start')
                        ->limit(3)
                        ->get(['id', 'period_start', 'period_end', 'status', 'supplier_invoice_number', 'supplier_invoice_date']);
                    foreach ($recent as $pb) {
                        $recentBillings[] = [
                            'period_label' => $pb->period_start?->locale('tr')->translatedFormat('F Y') ?? '—',
                            'status' => $pb->status,
                            'status_label' => match ($pb->status) {
                                PendingBilling::STATUS_PENDING => 'Beklemede',
                                PendingBilling::STATUS_POSTPONED => 'Ertelendi',
                                PendingBilling::STATUS_INVOICED => 'Faturalandı',
                                PendingBilling::STATUS_CANCELLED => 'İptal',
                                default => $pb->status,
                            },
                            'has_supplier_invoice' => $pb->supplier_invoice_number !== null && trim((string) $pb->supplier_invoice_number) !== '' || $pb->supplier_invoice_date !== null,
                            'has_sales_invoice' => $pb->salesInvoiceLine !== null,
                        ];
                    }
                    // Cari kısa adı / unvanı
                    $lineCustomerNames[$index] = $subscription->customerCari?->short_name ?: $subscription->customerCari?->name;
                }
            }
            $linePeriods[$index] = $periods;
            $lineRecentBillings[$index] = $recentBillings;
        }

        return view('pending-billings.supplier-invoice-xml-preview', [
            'parsed' => $data,
            'linePeriods' => $linePeriods,
            'lineRecentBillings' => $lineRecentBillings,
            'lineCustomerNames' => $lineCustomerNames,
            'defaultPeriodYear' => $defaultPeriodYear,
            'defaultPeriodMonth' => $defaultPeriodMonth,
            'unmatched' => $request->session()->get('unmatched', []),
        ]);
    }

    public function cancelSupplierInvoiceXmlPreview(Request $request): RedirectResponse
    {
        $data = $request->session()->get('supplier_invoice_xml_parsed');
        $backStatus = is_array($data) ? ($data['back_status'] ?? 'pending') : 'pending';
        $request->session()->forget('supplier_invoice_xml_parsed');

        return redirect()->route('pending-billings.index', ['status' => $backStatus])
            ->with('success', 'XML önizlemesi iptal edildi.');
    }

    public function applySupplierInvoiceXml(Request $request): RedirectResponse
    {
        $data = $request->session()->get('supplier_invoice_xml_parsed');
        if (! is_array($data)) {
            return redirect()->route('pending-billings.index')->with('error', 'Önizleme verisi bulunamadı. XML’i tekrar yükleyin.');
        }

        $providerCariId = (int) ($data['provider_cari_id'] ?? 0);
        $invoiceNo = $data['invoice_no'] ?? '';
        $issueDateStr = $data['issue_date'] ?? '';
        $lines = $data['lines'] ?? [];
        $backStatus = $data['back_status'] ?? 'pending';

        if ($issueDateStr === '' || $lines === []) {
            $request->session()->forget('supplier_invoice_xml_parsed');

            return redirect()->route('pending-billings.index', ['status' => $backStatus])
                ->with('error', 'Fatura tarihi veya kalem yok.');
        }

        $issueDate = Carbon::parse($issueDateStr);
        $requestLines = $request->input('lines', []);

        $unmatched = [];
        $resolved = [];

        foreach ($lines as $index => $line) {
            $sn = trim((string) ($line['sozlesme_no'] ?? ''));
            if ($sn === '') {
                continue;
            }
            $periodStr = isset($requestLines[$index]['period']) ? trim((string) $requestLines[$index]['period']) : '';
            $periodParts = $periodStr !== '' ? explode('-', $periodStr) : [];
            $periodYear = (count($periodParts) === 2 && is_numeric($periodParts[0]) && is_numeric($periodParts[1]))
                ? (int) $periodParts[0] : null;
            $periodMonth = (count($periodParts) === 2 && is_numeric($periodParts[0]) && is_numeric($periodParts[1]))
                ? (int) $periodParts[1] : null;
            if ($periodYear === null || $periodMonth === null || $periodMonth < 1 || $periodMonth > 12) {
                $unmatched[] = ['sozlesme_no' => $sn, 'item_name' => $line['item_name'] ?? null, 'reason' => 'Dönem seçilmedi'];
                continue;
            }

            $subscription = Subscription::query()
                ->where('provider_cari_id', $providerCariId)
                ->where('sozlesme_no', $sn)
                ->first();
            if (! $subscription) {
                $unmatched[] = ['sozlesme_no' => $sn, 'item_name' => $line['item_name'] ?? null, 'reason' => 'Abonelik bulunamadı'];
                continue;
            }

            $pendingBilling = PendingBilling::query()
                ->where('subscription_id', $subscription->id)
                ->whereYear('period_start', $periodYear)
                ->whereMonth('period_start', $periodMonth)
                ->first();
            if (! $pendingBilling) {
                $unmatched[] = ['sozlesme_no' => $sn, 'item_name' => $line['item_name'] ?? null, 'reason' => $periodYear . '-' . str_pad((string) $periodMonth, 2, '0', STR_PAD_LEFT) . ' döneminde sipariş yok'];
                continue;
            }

            $key = $sn . '|' . $periodYear . '|' . $periodMonth;
            if (! isset($resolved[$key])) {
                $resolved[$key] = [
                    'subscription' => $subscription,
                    'pending_billing' => $pendingBilling,
                    'amount' => 0,
                    'quantity' => 0,
                ];
            }
            $resolved[$key]['amount'] += (float) ($line['line_extension_amount_try'] ?? 0);
            $resolved[$key]['quantity'] += (float) ($line['quantity'] ?? 0);
        }

        if ($unmatched !== []) {
            return redirect()
                ->route('pending-billings.supplier-invoice-xml-preview')
                ->with('error', 'Faturada olup siparişlerinizde bulunamayan kalemler var. Abonelik tanımlı olmayabilir veya dönem eşleşmiyor. İşlem tamamlanmadı.')
                ->with('unmatched', $unmatched);
        }

        $updated = 0;
        $quantityUpdated = 0;
        foreach ($resolved as $row) {
            $subscription = $row['subscription'];
            $pendingBilling = $row['pending_billing'];
            $amount = $row['amount'];

            $pendingBilling->update([
                'supplier_invoice_number' => $invoiceNo,
                'supplier_invoice_date' => $issueDate,
                'actual_alis_tl' => $amount,
            ]);

            $satisFromAlis = null;
            if ((float) $subscription->usd_birim_alis > 0 && $subscription->usd_birim_satis !== null) {
                $satisFromAlis = (float) $amount * ((float) $subscription->usd_birim_satis / (float) $subscription->usd_birim_alis);
            }

            $salesLine = $pendingBilling->salesInvoiceLine;
            if ($salesLine !== null) {
                $pendingBilling->update(['expected_satis_tl' => $satisFromAlis]);
                $feeDifferenceTl = null;
                if ($satisFromAlis !== null && $salesLine->line_amount_tl !== null && $salesLine->line_amount_tl !== '') {
                    $feeDifferenceTl = (float) $satisFromAlis - (float) $salesLine->line_amount_tl;
                }
                $pendingBilling->update(['fee_difference_tl' => $feeDifferenceTl]);
            } else {
                $pendingBilling->update(['expected_satis_tl' => $satisFromAlis]);
            }

            // XML satırlarındaki adet toplamına göre abonelik miktarını güncelle
            $newQuantity = (int) round($row['quantity']);
            $previousQuantity = (int) $subscription->quantity;
            if ($newQuantity > 0 && $newQuantity !== $previousQuantity) {
                SubscriptionQuantityChange::create([
                    'subscription_id' => $subscription->id,
                    'previous_quantity' => $previousQuantity,
                    'new_quantity' => $newQuantity,
                    'effective_date' => $issueDate,
                ]);
                $subscription->update(['quantity' => $newQuantity]);
                $quantityUpdated++;
            }

            $updated++;
        }

        $request->session()->forget('supplier_invoice_xml_parsed');

        $message = 'Alış faturası XML işlendi. ' . $updated . ' sipariş güncellendi.';
        if ($quantityUpdated > 0) {
            $message .= ' ' . $quantityUpdated . ' abonelik için adet güncellendi.';
        }

        return redirect()
            ->route('pending-billings.index', ['status' => $backStatus])
            ->with('success', $message);
    }
}
