<?php

namespace App\Http\Controllers;

use App\Models\Cari;
use App\Models\ExchangeRate;
use App\Models\PendingBilling;
use App\Models\SalesInvoice;
use App\Models\SalesInvoiceLine;
use App\Services\SalesInvoiceXmlParser;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class SalesInvoiceController extends Controller
{
    public function index(): View
    {
        $salesInvoices = SalesInvoice::query()
            ->with(['customerCari', 'lines.pendingBilling.subscription.product'])
            ->latest()
            ->paginate(15);

        return view('sales-invoices.index', compact('salesInvoices'));
    }

    public function showSalesInvoiceXml(): View
    {
        return view('sales-invoices.sales-invoice-xml');
    }

    public function storeSalesInvoiceXml(Request $request, SalesInvoiceXmlParser $parser): RedirectResponse
    {
        $validated = $request->validate([
            'xml_file' => ['nullable', 'file', 'max:10240', 'required_without:xml_content'],
            'xml_content' => ['nullable', 'string', 'required_without:xml_file'],
        ]);

        $xmlContent = null;
        if (! empty($validated['xml_content'] ?? null)) {
            $xmlContent = (string) $validated['xml_content'];
        } elseif ($request->hasFile('xml_file')) {
            $xmlContent = file_get_contents($validated['xml_file']->getRealPath());
        }

        if ($xmlContent === null || trim($xmlContent) === '') {
            return redirect()
                ->route('sales-invoices.sales-invoice-xml')
                ->withInput()
                ->with('error', 'XML içeriği bulunamadı. Dosya seçin veya XML metnini yapıştırın.');
        }

        $parsed = $parser->parse($xmlContent);

        if ($parsed['customer_vkn'] === null || $parsed['customer_vkn'] === '') {
            return redirect()
                ->route('sales-invoices.sales-invoice-xml')
                ->withInput()
                ->with('error', 'XML’de alıcı (müşteri) VKN/TCKN bulunamadı.');
        }

        $vknNormalized = preg_replace('/\s+/', '', $parsed['customer_vkn']);
        $cari = Cari::where('country_code', 'TR')
            ->whereNotNull('tax_number')
            ->get()
            ->first(fn (Cari $c) => preg_replace('/\s+/', '', (string) $c->tax_number) === $vknNormalized);

        if (! $cari) {
            return redirect()
                ->route('sales-invoices.sales-invoice-xml')
                ->withInput()
                ->with('error', 'Bu VKN/TCKN’e ait cari bulunamadı: ' . $parsed['customer_vkn']);
        }

        $periodYear = null;
        $periodMonth = null;
        if ($parsed['issue_date'] !== null && $parsed['issue_date'] !== '') {
            $dt = \DateTime::createFromFormat('Y-m-d', $parsed['issue_date']);
            if ($dt) {
                $periodYear = (int) $dt->format('Y');
                $periodMonth = (int) $dt->format('n');
            }
        }
        if ($periodYear === null || $periodMonth === null) {
            return redirect()
                ->route('sales-invoices.sales-invoice-xml')
                ->withInput()
                ->with('error', 'XML’de fatura tarihi (IssueDate) bulunamadı veya geçersiz.');
        }

        $sozlesmeNos = $parsed['sozlesme_nos'];
        if ($sozlesmeNos === []) {
            return redirect()
                ->route('sales-invoices.sales-invoice-xml')
                ->withInput()
                ->with('error', 'XML’de *sözleşme no* formatında açıklama bulunamadı (örn. *23452436*).');
        }

        $candidates = SalesInvoice::query()
            ->where(function ($q): void {
                $q->whereNull('our_invoice_number')->orWhere('our_invoice_number', '');
            })
            ->where('customer_cari_id', $cari->id)
            ->whereHas('lines.pendingBilling', function ($q) use ($periodYear, $periodMonth): void {
                $q->whereYear('period_start', $periodYear)->whereMonth('period_start', $periodMonth);
            })
            ->whereHas('lines.pendingBilling.subscription', function ($q) use ($sozlesmeNos): void {
                $q->whereIn('sozlesme_no', $sozlesmeNos);
            })
            ->with(['customerCari', 'lines.pendingBilling.subscription'])
            ->orderByDesc('id')
            ->get();

        $request->session()->put('sales_invoice_xml_parsed', [
            'parsed' => $parsed,
            'cari_id' => $cari->id,
            'cari_name' => $cari->short_name ?: $cari->name,
            'candidates' => $candidates->map(fn (SalesInvoice $inv) => [
                'id' => $inv->id,
                'order_number' => $inv->order_number,
                'total_amount_tl' => $inv->total_amount_tl !== null ? (float) $inv->total_amount_tl : null,
                'line_count' => $inv->lines->count(),
                'sozlesme_nos' => $inv->lines->pluck('pendingBilling.subscription.sozlesme_no')->filter()->unique()->values()->all(),
            ])->all(),
        ]);

        return redirect()->route('sales-invoices.sales-invoice-xml-match-preview');
    }

    public function showSalesInvoiceXmlMatchPreview(Request $request): View|RedirectResponse
    {
        $data = $request->session()->get('sales_invoice_xml_parsed');
        if (! is_array($data) || ! isset($data['parsed'], $data['candidates'])) {
            return redirect()->route('sales-invoices.sales-invoice-xml')
                ->with('error', 'Önizleme verisi yok. XML’i tekrar yükleyin.');
        }

        $parsed = $data['parsed'];
        $candidates = $data['candidates'];
        $xmlTaxExclusive = (float) ($parsed['tax_exclusive_amount'] ?? 0);

        return view('sales-invoices.sales-invoice-xml-match-preview', [
            'parsed' => $parsed,
            'cariName' => $data['cari_name'] ?? '',
            'candidates' => $candidates,
            'xmlTaxExclusiveAmount' => $xmlTaxExclusive,
        ]);
    }

    public function cancelSalesInvoiceXmlMatch(Request $request): RedirectResponse
    {
        $request->session()->forget('sales_invoice_xml_parsed');

        return redirect()->route('sales-invoices.sales-invoice-xml')
            ->with('info', 'Eşleştirme iptal edildi.');
    }

    public function confirmSalesInvoiceXmlMatch(Request $request): RedirectResponse
    {
        $data = $request->session()->get('sales_invoice_xml_parsed');
        if (! is_array($data) || ! isset($data['parsed'], $data['candidates'])) {
            return redirect()->route('sales-invoices.sales-invoice-xml')
                ->with('error', 'Oturum süresi doldu. XML’i tekrar yükleyin.');
        }

        $validated = $request->validate([
            'sales_invoice_id' => ['required', 'integer', 'exists:sales_invoices,id'],
        ]);

        $salesInvoiceId = (int) $validated['sales_invoice_id'];
        $allowedIds = array_column($data['candidates'], 'id');
        if (! in_array($salesInvoiceId, $allowedIds, true)) {
            return redirect()->route('sales-invoices.sales-invoice-xml-match-preview')
                ->with('error', 'Seçilen fatura eşleşen listede yok.');
        }

        $parsed = $data['parsed'];
        $salesInvoice = SalesInvoice::findOrFail($salesInvoiceId);

        $update = [
            'our_invoice_number' => $parsed['invoice_id'] ?? '',
            'our_invoice_date' => $parsed['issue_date'] ?? null,
        ];
        if ($salesInvoice->order_number === null || $salesInvoice->order_number === '') {
            $update['order_number'] = SalesInvoice::getNextFaturaTakipNo();
        }
        $salesInvoice->update($update);

        $request->session()->forget('sales_invoice_xml_parsed');

        return redirect()
            ->route('sales-invoices.show', $salesInvoice)
            ->with('success', 'Fatura eşleştirildi. Fatura no ve tarih kaydedildi.');
    }

    public function create(Request $request): View|RedirectResponse
    {
        $customerCaris = Cari::whereIn('cari_type', ['customer', 'both'])
            ->orderBy('name')
            ->get(['id', 'name', 'short_name']);

        $customerCariId = $request->get('customer_cari_id');
        $pendingBillings = collect();
        $fromSelection = false;

        $ids = $request->get('pending_billing_ids', []);
        if (is_array($ids)) {
            $ids = array_values(array_unique(array_filter(array_map('intval', $ids))));
        } else {
            $ids = [];
        }

        if ($ids !== []) {
            $pendingBillings = PendingBilling::query()
                ->with(['subscription.product', 'subscription.customerCari'])
                ->whereIn('status', [PendingBilling::STATUS_PENDING, PendingBilling::STATUS_POSTPONED])
                ->whereIn('id', $ids)
                ->orderBy('period_start')
                ->get();

            if ($pendingBillings->isEmpty()) {
                return redirect()->route('pending-billings.index', ['status' => 'pending'])
                    ->with('error', 'Seçilen siparişler bulunamadı veya artık beklemede/ertelenmiş değil.');
            }

            $customerCariIds = $pendingBillings->pluck('subscription.customer_cari_id')->unique()->filter()->values()->all();
            if (count($customerCariIds) > 1) {
                return redirect()->route('pending-billings.index', ['status' => 'pending'])
                    ->with('error', 'Seçilen siparişler farklı müşterilere ait. Aynı müşterinin siparişlerini seçin.');
            }

            $customerCariId = (string) $customerCariIds[0];
            $fromSelection = true;
        } elseif ($customerCariId) {
            $pendingBillings = PendingBilling::query()
                ->with(['subscription.product', 'subscription.customerCari'])
                ->where('status', PendingBilling::STATUS_PENDING)
                ->whereHas('subscription', fn ($q) => $q->where('customer_cari_id', $customerCariId))
                ->orderBy('period_start')
                ->get();
        }

        $accumulatedFarkBySubscription = [];
        if ($pendingBillings->isNotEmpty()) {
            $subscriptionIds = $pendingBillings->pluck('subscription_id')->unique()->filter()->values()->all();
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
        }

        $usdEfektifSelling = $this->getUsdEfektifSelling();

        return view('sales-invoices.create', [
            'customerCaris' => $customerCaris,
            'customerCariId' => $customerCariId,
            'pendingBillings' => $pendingBillings,
            'accumulatedFarkBySubscription' => $accumulatedFarkBySubscription,
            'fromSelection' => $fromSelection,
            'usdEfektifSelling' => $usdEfektifSelling,
        ]);
    }

    private function getUsdEfektifSelling(): ?float
    {
        $usdToday = ExchangeRate::where('currency_code', 'USD')
            ->where('effective_date', now()->toDateString())
            ->first();
        if ($usdToday?->forex_selling !== null && $usdToday->forex_selling !== '') {
            return (float) $usdToday->forex_selling;
        }
        $usdLast = ExchangeRate::where('currency_code', 'USD')
            ->whereNotNull('forex_selling')
            ->orderByDesc('effective_date')
            ->first();

        return $usdLast?->forex_selling !== null && $usdLast->forex_selling !== '' ? (float) $usdLast->forex_selling : null;
    }

    private function baseSatisTlForPendingBilling(PendingBilling $pb, ?float $usdRate): float
    {
        $val = $pb->actual_satis_tl ?? $pb->expected_satis_tl;
        if ($val !== null && $val !== '') {
            return (float) $val;
        }
        if ($usdRate === null) {
            return 0.0;
        }
        $sub = $pb->subscription;
        $usdAlis = $sub->usd_birim_alis !== null && $sub->usd_birim_alis !== '' ? (float) $sub->usd_birim_alis : null;
        $usdSatis = $sub->usd_birim_satis !== null && $sub->usd_birim_satis !== '' ? (float) $sub->usd_birim_satis : null;
        if ($usdAlis === null || $usdAlis <= 0 || $usdSatis === null) {
            return 0.0;
        }
        $qty = (int) $sub->quantity;
        $alisKdvHaric = $usdAlis * $qty * $usdRate;

        return $alisKdvHaric * ($usdSatis / $usdAlis);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'customer_cari_id' => ['required', 'exists:caris,id'],
            'pending_billing_ids' => ['required', 'array', 'min:1'],
            'pending_billing_ids.*' => ['required', 'integer', 'exists:pending_billings,id'],
            'add_fark' => ['nullable', 'array'],
            'add_fark.*' => ['integer', 'exists:pending_billings,id'],
        ]);

        $customerCariId = (int) $validated['customer_cari_id'];
        $ids = array_values(array_unique(array_map('intval', $validated['pending_billing_ids'])));
        $rawAddFark = $validated['add_fark'] ?? [];
        if (! is_array($rawAddFark)) {
            $rawAddFark = $rawAddFark !== null && $rawAddFark !== '' ? [$rawAddFark] : [];
        }
        $addFarkIds = array_values(array_intersect(
            array_unique(array_filter(array_map('intval', $rawAddFark))),
            $ids
        ));

        $pendingBillings = PendingBilling::query()
            ->with('subscription')
            ->whereIn('id', $ids)
            ->where('status', PendingBilling::STATUS_PENDING)
            ->whereHas('subscription', fn ($q) => $q->where('customer_cari_id', $customerCariId))
            ->get();

        if ($pendingBillings->isEmpty()) {
            return redirect()
                ->route('sales-invoices.create', ['customer_cari_id' => $customerCariId])
                ->with('error', 'Seçilen kayıtlar bulunamadı veya müşteri uyuşmuyor.');
        }

        // Güvenlik için: tek müşteri ve tek dönem kuralını backend'de de zorunlu kıl
        $customerIds = $pendingBillings
            ->pluck('subscription.customer_cari_id')
            ->filter()
            ->unique()
            ->values();

        if ($customerIds->count() !== 1 || (int) $customerIds[0] !== $customerCariId) {
            return redirect()
                ->route('sales-invoices.create', ['customer_cari_id' => $customerCariId])
                ->with('error', 'Farklı carilere ait siparişler aynı faturaya geçirilemez.');
        }

        // Dönem kontrolü: aynı faturada yalnızca aynı yıl/ay (örneğin Şubat 2026) olmalı
        $periods = $pendingBillings
            ->pluck('period_start')
            ->filter()
            ->map(fn ($d) => $d->format('Y-m')) // yıl + ay bazında normalize et
            ->unique()
            ->values();

        if ($periods->count() > 1) {
            return redirect()
                ->route('sales-invoices.create', ['pending_billing_ids' => $ids])
                ->with('error', 'Farklı aylara ait siparişler aynı faturaya geçirilemez. Lütfen tek bir aya ait siparişleri seçin.');
        }

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

        $usdRate = $this->getUsdEfektifSelling();
        $farkAddedForSubscription = [];
        $total = 0;
        foreach ($pendingBillings as $pb) {
            $base = $this->baseSatisTlForPendingBilling($pb, $usdRate);
            $farkToAdd = 0;
            if (in_array((int) $pb->id, $addFarkIds, true) && ! isset($farkAddedForSubscription[$pb->subscription_id])) {
                $farkToAdd = $accumulatedFarkBySubscription[$pb->subscription_id] ?? 0;
                $farkAddedForSubscription[$pb->subscription_id] = true;
            }
            $lineAmount = $base + $farkToAdd;
            $total += $lineAmount;
        }

        $salesInvoice = SalesInvoice::create([
            'customer_cari_id' => $customerCariId,
            'total_amount_tl' => $total,
            'order_number' => SalesInvoice::getNextFaturaTakipNo(),
        ]);

        $farkAddedForSubscription = [];
        foreach ($pendingBillings as $pb) {
            $base = $this->baseSatisTlForPendingBilling($pb, $usdRate);
            $farkToAdd = 0;
            if (in_array((int) $pb->id, $addFarkIds, true) && ! isset($farkAddedForSubscription[$pb->subscription_id])) {
                $farkToAdd = $accumulatedFarkBySubscription[$pb->subscription_id] ?? 0;
                $farkAddedForSubscription[$pb->subscription_id] = true;
            }
            $lineAmount = $base + $farkToAdd;
            SalesInvoiceLine::create([
                'sales_invoice_id' => $salesInvoice->id,
                'pending_billing_id' => $pb->id,
                'line_amount_tl' => $lineAmount,
            ]);
            // Faturalandığında fatura tutarı kesinleşen satış olarak kayda yazılır (beklenen satış → kesinleşen satış)
            $pb->update([
                'status' => PendingBilling::STATUS_INVOICED,
                'actual_satis_tl' => $lineAmount,
            ]);
        }

        return redirect()
            ->route('sales-invoices.show', $salesInvoice)
            ->with('success', 'Faturalandırma oluşturuldu. ' . $pendingBillings->count() . ' kayıt faturalandı.');
    }

    public function show(SalesInvoice $sales_invoice): View
    {
        $sales_invoice->load([
            'customerCari',
            'lines.pendingBilling.subscription.product',
            'lines.pendingBilling.subscription.customerCari',
        ]);

        return view('sales-invoices.show', ['salesInvoice' => $sales_invoice]);
    }

    public function editInvoiceDetails(SalesInvoice $sales_invoice): View
    {
        $sales_invoice->load('customerCari');

        return view('sales-invoices.invoice-details', ['salesInvoice' => $sales_invoice]);
    }

    public function updateInvoiceDetails(Request $request, SalesInvoice $sales_invoice): RedirectResponse
    {
        $validated = $request->validate([
            'our_invoice_number' => ['required', 'string', 'max:64'],
            'our_invoice_date' => ['required', 'date'],
            'invoice_total_net_tl' => ['nullable', 'numeric', 'min:0'],
            'invoice_total_diff_reason' => ['nullable', 'string', 'max:255'],
        ]);

        $orderNumber = $sales_invoice->order_number;
        if ($orderNumber === null || $orderNumber === '') {
            $orderNumber = SalesInvoice::getNextFaturaTakipNo();
        }

        $invoiceTotalNet = $validated['invoice_total_net_tl'] ?? null;
        $invoiceTotalDiff = null;
        if ($invoiceTotalNet !== null) {
            $baseTotal = $sales_invoice->total_amount_tl !== null ? (float) $sales_invoice->total_amount_tl : 0.0;
            $invoiceTotalDiff = (float) $invoiceTotalNet - $baseTotal;
        }

        $diffReason = $validated['invoice_total_diff_reason'] ?? null;
        if ($invoiceTotalDiff === null || (float) $invoiceTotalDiff === 0.0) {
            // Fark yoksa açıklamayı sıfırla
            $diffReason = null;
        }

        $sales_invoice->update([
            'our_invoice_number' => $validated['our_invoice_number'],
            'our_invoice_date' => $validated['our_invoice_date'],
            'order_number' => $orderNumber,
            'invoice_total_net_tl' => $invoiceTotalNet,
            'invoice_total_diff_tl' => $invoiceTotalDiff,
            'invoice_total_diff_reason' => $diffReason,
        ]);

        return redirect()
            ->route('sales-invoices.index')
            ->with('success', 'Fatura numarası ve tarihi kaydedildi.');
    }
}
