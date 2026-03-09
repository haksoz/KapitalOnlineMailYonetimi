<?php

namespace App\Http\Controllers;

use App\Models\Cari;
use App\Models\ExchangeRate;
use App\Models\PendingBilling;
use App\Models\SalesInvoice;
use App\Models\SalesInvoiceLine;
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
                ->where('status', PendingBilling::STATUS_PENDING)
                ->whereIn('id', $ids)
                ->orderBy('period_start')
                ->get();

            if ($pendingBillings->isEmpty()) {
                return redirect()->route('pending-billings.index', ['status' => 'pending'])
                    ->with('error', 'Seçilen siparişler bulunamadı veya artık beklemede değil.');
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
        ]);

        $sales_invoice->update([
            'our_invoice_number' => $validated['our_invoice_number'],
            'our_invoice_date' => $validated['our_invoice_date'],
        ]);

        return redirect()
            ->route('sales-invoices.index')
            ->with('success', 'Fatura numarası ve tarihi kaydedildi.');
    }
}
