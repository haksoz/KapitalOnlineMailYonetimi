<?php

namespace App\Http\Controllers;

use App\Models\ExchangeRate;
use App\Models\ExpenseSettlement;
use App\Models\ExpenseSettlementLine;
use App\Models\PendingBilling;
use App\Services\PendingBillingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ExpenseSettlementController extends Controller
{
    public function index(Request $request): View
    {
        $search = $request->get('search');

        $query = ExpenseSettlement::query()
            ->with(['customerCari', 'lines.pendingBilling.subscription.product']);

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('gider_number', 'like', '%' . $search . '%')
                    ->orWhereHas('customerCari', function ($q) use ($search) {
                        $q->where('name', 'like', '%' . $search . '%')
                            ->orWhere('short_name', 'like', '%' . $search . '%');
                    });
            });
        }

        $expenseSettlements = $query->latest()->paginate(15);

        return view('expense-settlements.index', compact('expenseSettlements'));
    }

    public function create(Request $request): View|RedirectResponse
    {
        $ids = $request->get('pending_billing_ids', []);
        if (is_array($ids)) {
            $ids = array_values(array_unique(array_filter(array_map('intval', $ids))));
        } else {
            $ids = [];
        }

        if ($ids === []) {
            return redirect()->route('pending-billings.index', ['status' => 'pending'])
                ->with('error', 'Giderleştirmek için en az bir sipariş seçin.');
        }

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
        $usdEfektifSelling = $this->getUsdEfektifSelling();

        return view('expense-settlements.create', [
            'customerCariId' => $customerCariId,
            'pendingBillings' => $pendingBillings,
            'usdEfektifSelling' => $usdEfektifSelling,
        ]);
    }

    public function store(Request $request, PendingBillingService $pendingBillingService): RedirectResponse
    {
        $validated = $request->validate([
            'customer_cari_id' => ['required', 'exists:caris,id'],
            'pending_billing_ids' => ['required', 'array', 'min:1'],
            'pending_billing_ids.*' => ['required', 'integer', 'exists:pending_billings,id'],
            'line_amounts' => ['required', 'array'],
            'line_amounts.*' => ['required', 'numeric', 'min:0'],
            'settlement_date' => ['nullable', 'date'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $customerCariId = (int) $validated['customer_cari_id'];
        $ids = array_values(array_unique(array_map('intval', $validated['pending_billing_ids'])));
        $rawLineAmounts = $validated['line_amounts'] ?? [];
        $lineAmountsById = [];
        foreach ($ids as $id) {
            if (! array_key_exists((string) $id, $rawLineAmounts) && ! array_key_exists($id, $rawLineAmounts)) {
                return redirect()
                    ->back()
                    ->withInput()
                    ->withErrors(['line_amounts' => 'Seçilen her sipariş için tutar girilmelidir.']);
            }
            $lineAmountsById[$id] = (float) ($rawLineAmounts[$id] ?? $rawLineAmounts[(string) $id]);
        }

        $pendingBillings = PendingBilling::query()
            ->with('subscription')
            ->whereIn('id', $ids)
            ->where('status', PendingBilling::STATUS_PENDING)
            ->whereHas('subscription', fn ($q) => $q->where('customer_cari_id', $customerCariId))
            ->get();

        if ($pendingBillings->isEmpty()) {
            return redirect()
                ->route('pending-billings.index', ['status' => 'pending'])
                ->with('error', 'Seçilen kayıtlar bulunamadı veya müşteri uyuşmuyor.');
        }

        $customerIds = $pendingBillings
            ->pluck('subscription.customer_cari_id')
            ->filter()
            ->unique()
            ->values();

        if ($customerIds->count() !== 1 || (int) $customerIds[0] !== $customerCariId) {
            return redirect()
                ->route('pending-billings.index', ['status' => 'pending'])
                ->with('error', 'Farklı carilere ait siparişler aynı giderleştirmede birleştirilemez.');
        }

        $usdRate = $this->getUsdEfektifSelling();

        if ($usdRate !== null) {
            foreach ($pendingBillings as $pb) {
                $missingExpectedAlis = $pb->expected_alis_tl === null || $pb->expected_alis_tl === '';
                $missingExpectedSatis = $pb->expected_satis_tl === null || $pb->expected_satis_tl === '';
                if ($missingExpectedAlis || $missingExpectedSatis) {
                    $pendingBillingService->refreshAmountsForRecord($pb, $usdRate);
                    $pb->refresh();
                }
            }
        }

        $settlementDate = $validated['settlement_date'] ?? now()->toDateString();
        $notes = isset($validated['notes']) ? trim((string) $validated['notes']) : '';
        if ($notes === '') {
            $notes = null;
        }

        $expenseSettlement = DB::transaction(function () use ($pendingBillings, $customerCariId, $lineAmountsById, $settlementDate, $notes) {
            $total = 0.0;
            $resolved = [];
            foreach ($pendingBillings as $pb) {
                if (! array_key_exists((int) $pb->id, $lineAmountsById)) {
                    throw new \InvalidArgumentException('Missing line amount for pending billing '.$pb->id);
                }
                $lineAmount = $lineAmountsById[(int) $pb->id];
                $resolved[$pb->id] = $lineAmount;
                $total += $lineAmount;
            }

            $settlement = ExpenseSettlement::create([
                'customer_cari_id' => $customerCariId,
                'gider_number' => ExpenseSettlement::getNextGiderNo(),
                'settlement_date' => $settlementDate,
                'total_amount_tl' => $total,
                'notes' => $notes,
            ]);

            foreach ($pendingBillings as $pb) {
                $lineAmount = $resolved[$pb->id];
                ExpenseSettlementLine::create([
                    'expense_settlement_id' => $settlement->id,
                    'pending_billing_id' => $pb->id,
                    'line_amount_tl' => $lineAmount,
                ]);
                $pb->update([
                    'status' => PendingBilling::STATUS_EXPENSED,
                    'actual_satis_tl' => $lineAmount,
                ]);
            }

            return $settlement;
        });

        return redirect()
            ->route('expense-settlements.show', $expenseSettlement)
            ->with('success', 'Giderleştirme oluşturuldu. ' . $pendingBillings->count() . ' kayıt giderleştirildi.');
    }

    public function show(ExpenseSettlement $expense_settlement): View
    {
        $expense_settlement->load([
            'customerCari',
            'lines.pendingBilling.subscription.product',
            'lines.pendingBilling.subscription.customerCari',
        ]);

        return view('expense-settlements.show', ['expenseSettlement' => $expense_settlement]);
    }

    public function revert(ExpenseSettlement $expense_settlement): RedirectResponse
    {
        $revertedCount = 0;
        $reverted = DB::transaction(function () use ($expense_settlement, &$revertedCount): bool {
            $lines = $expense_settlement->lines()
                ->select(['id', 'pending_billing_id'])
                ->lockForUpdate()
                ->get();

            $pendingBillingIds = $lines->pluck('pending_billing_id')->filter()->values()->all();
            if ($pendingBillingIds === []) {
                return false;
            }

            $pendingBillings = PendingBilling::query()
                ->whereIn('id', $pendingBillingIds)
                ->lockForUpdate()
                ->get();

            if ($pendingBillings->count() !== count($pendingBillingIds)
                || $pendingBillings->contains(fn (PendingBilling $pb): bool => $pb->status !== PendingBilling::STATUS_EXPENSED)) {
                return false;
            }

            PendingBilling::query()
                ->whereIn('id', $pendingBillingIds)
                ->update([
                    'status' => PendingBilling::STATUS_PENDING,
                    'actual_satis_tl' => null,
                    'fee_difference_tl' => null,
                ]);

            $expense_settlement->lines()->delete();
            $expense_settlement->delete();
            $revertedCount = count($pendingBillingIds);

            return true;
        });

        if (! $reverted) {
            return redirect()
                ->route('expense-settlements.show', $expense_settlement)
                ->with('error', 'Giderleştirme geri alınamadı. Bağlı siparişlerin durumunu kontrol edin.');
        }

        return redirect()
            ->route('pending-billings.index', ['status' => PendingBilling::STATUS_PENDING])
            ->with('success', $revertedCount . ' sipariş tekrar bekleyen siparişlere alındı.');
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
}
