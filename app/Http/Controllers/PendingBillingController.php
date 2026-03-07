<?php

namespace App\Http\Controllers;

use App\Models\ExchangeRate;
use App\Models\PendingBilling;
use App\Models\SubscriptionQuantityChange;
use App\Services\PendingBillingService;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class PendingBillingController extends Controller
{
    public function index(Request $request): View
    {
        $query = PendingBilling::query()
            ->with(['subscription.customerCari', 'subscription.product'])
            ->orderByDesc('period_start');

        $status = $request->get('status', PendingBilling::STATUS_PENDING);
        if (in_array($status, [PendingBilling::STATUS_PENDING, PendingBilling::STATUS_INVOICED, PendingBilling::STATUS_CANCELLED], true)) {
            $query->where('status', $status);
        } else {
            $query->where('status', PendingBilling::STATUS_PENDING);
            $status = PendingBilling::STATUS_PENDING;
        }

        $pendingBillings = $query->paginate(20)->withQueryString();

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

    public function showSupplierInvoice(PendingBilling $pending_billing): View
    {
        $pending_billing->load('subscription');

        return view('pending-billings.supplier-invoice', [
            'pendingBilling' => $pending_billing,
        ]);
    }

    public function storeSupplierInvoice(Request $request, PendingBilling $pending_billing): RedirectResponse
    {
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
            // Zaten faturalandı: gerçek satış ve farkı yaz (fatura tutarı ile karşılaştır)
            $pending_billing->update(['actual_satis_tl' => $satisFromAlis]);
            $feeDifferenceTl = $satisFromAlis !== null ? $satisFromAlis - (float) $line->line_amount_tl : null;
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
}
