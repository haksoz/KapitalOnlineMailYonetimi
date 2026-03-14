<?php

namespace App\Http\Controllers;

use App\Models\Cari;
use App\Models\PendingBilling;
use App\Models\SalesInvoiceLine;
use App\Models\Subscription;
use App\Services\PendingBillingService;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class SubscriptionMonitorController extends Controller
{
    public function index(Request $request): View
    {
        $year = (int) $request->input('year', Carbon::today()->year);
        $month = (int) $request->input('month', Carbon::today()->month);

        $monthStart = Carbon::create($year, $month, 1)->startOfDay();
        $monthEnd = $monthStart->copy()->endOfMonth();

        // Bu ay ile kesişen aktif abonelikler
        $subscriptions = Subscription::query()
            ->where('durum', Subscription::DURUM_ACTIVE)
            ->whereNotNull('baslangic_tarihi')
            ->whereNotNull('bitis_tarihi')
            ->where('baslangic_tarihi', '<=', $monthEnd)
            ->where('bitis_tarihi', '>', $monthStart)
            ->with('customerCari')
            ->get();

        // Cari bazlı grupla
        $byCustomer = $subscriptions->groupBy('customer_cari_id');

        $customerSummaries = [];
        $customerIds = $byCustomer->keys()->filter()->all();

        // İlgili cariler için bu ayın pending_billings ve faturaları
        if (! empty($customerIds)) {
            $pendingForMonth = PendingBilling::query()
                ->whereBetween('period_start', [$monthStart->toDateString(), $monthEnd->toDateString()])
                ->whereHas('subscription', function ($q) use ($customerIds) {
                    $q->whereIn('customer_cari_id', $customerIds);
                })
                ->with(['subscription.customerCari', 'salesInvoiceLine.salesInvoice'])
                ->get();

            $pendingBySubscription = $pendingForMonth->groupBy('subscription_id');

            /** @var \Illuminate\Support\Collection<int, SalesInvoiceLine> $invoiceLinesForMonth */
            $invoiceLinesForMonth = SalesInvoiceLine::query()
                ->whereIn('pending_billing_id', $pendingForMonth->pluck('id')->all())
                ->whereHas('salesInvoice', function ($q) use ($monthStart, $monthEnd) {
                    $q->whereBetween('our_invoice_date', [$monthStart->toDateString(), $monthEnd->toDateString()]);
                })
                ->with('salesInvoice')
                ->get();

            $invoicedPendingIds = $invoiceLinesForMonth
                ->pluck('pending_billing_id')
                ->filter()
                ->unique()
                ->values();

            foreach ($byCustomer as $customerId => $subs) {
                /** @var \Illuminate\Support\Collection<int, Subscription> $subs */
                /** @var Cari|null $cari */
                $cari = $subs->first()?->customerCari;

                // Bu ay için gerçekten dönem beklediğimiz abonelikleri filtrele.
                // - Aylık abonelikler: bu ay ile kesişen tüm aktifler (zaten $subscriptions içinde öyle geldiler)
                // - Yıllık abonelikler: sadece başlangıç ayı seçili ay ise o yıl için bir dönem beklenir
                $effectiveSubs = $subs->filter(function (Subscription $sub) use ($month): bool {
                    if ($sub->faturalama_periyodu === Subscription::FATURALAMA_YEARLY) {
                        return $sub->baslangic_tarihi !== null && $sub->baslangic_tarihi->month === $month;
                    }

                    return true;
                });

                $subscriptionCount = $effectiveSubs->count();
                if ($subscriptionCount === 0) {
                    // Bu ay için bu carinin hiçbir aboneliği dönem üretmiyor; satırı tamamen atla.
                    continue;
                }

                $expectedPeriods = $subscriptionCount;

                $customerSubscriptionIds = $effectiveSubs->pluck('id')->all();
                $pendingForCustomer = $pendingForMonth->whereIn('subscription_id', $customerSubscriptionIds);
                $pendingCount = $pendingForCustomer->count();

                // Alış faturası atanmış sipariş sayısı (supplier_invoice_number veya supplier_invoice_date dolu)
                $supplierInvoicedCount = $pendingForCustomer->filter(function ($pb) {
                    $hasNumber = $pb->supplier_invoice_number !== null && trim((string) $pb->supplier_invoice_number) !== '';
                    $hasDate = $pb->supplier_invoice_date !== null;

                    return $hasNumber || $hasDate;
                })->count();

                $invoicedCount = $pendingForCustomer
                    ->whereIn('id', $invoicedPendingIds)
                    ->count();

                $status = 'Tamamlandı';
                if ($pendingCount < $expectedPeriods) {
                    $status = 'Eksik sipariş var';
                } elseif ($invoicedCount < $pendingCount) {
                    $status = 'Faturalandırılmamış siparişler var';
                }

                $customerSummaries[] = [
                    'customer' => $cari,
                    'subscription_count' => $subscriptionCount,
                    'expected_periods' => $expectedPeriods,
                    'pending_count' => $pendingCount,
                    'supplier_invoiced_count' => $supplierInvoicedCount,
                    'invoiced_count' => $invoicedCount,
                    'status' => $status,
                ];
            }
        }

        // İstatistik kutucukları için toplamlar
        $totals = [
            'customer_count' => count($customerSummaries),
            'subscription_count' => array_sum(array_column($customerSummaries, 'subscription_count')),
            'expected_periods' => array_sum(array_column($customerSummaries, 'expected_periods')),
            'pending_count' => array_sum(array_column($customerSummaries, 'pending_count')),
            'supplier_invoiced_count' => array_sum(array_column($customerSummaries, 'supplier_invoiced_count')),
            'invoiced_count' => array_sum(array_column($customerSummaries, 'invoiced_count')),
        ];

        usort($customerSummaries, function (array $a, array $b): int {
            return strcmp($a['customer']?->name ?? '', $b['customer']?->name ?? '');
        });

        return view('subscription-monitor.index', [
            'year' => $year,
            'month' => $month,
            'monthStart' => $monthStart,
            'monthEnd' => $monthEnd,
            'customerSummaries' => $customerSummaries,
            'totals' => $totals,
        ]);
    }

    /**
     * Seçilen cari için, belirtilen aya kadar eksik dönem siparişlerini oluşturur.
     * Abone Takip listesinde "Bu ay için siparişleri oluştur" ile tetiklenir.
     */
    public function enqueueMissingForCari(Request $request, PendingBillingService $pendingBillingService): RedirectResponse
    {
        $validated = $request->validate([
            'customer_cari_id' => ['required', 'integer', 'exists:caris,id'],
            'year' => ['nullable', 'integer', 'min:2000', 'max:2100'],
            'month' => ['nullable', 'integer', 'min:1', 'max:12'],
        ]);

        $year = (int) ($validated['year'] ?? Carbon::today()->year);
        $month = (int) ($validated['month'] ?? Carbon::today()->month);
        $customerCariId = (int) $validated['customer_cari_id'];

        $upToDate = Carbon::create($year, $month, 1)->endOfMonth();

        $added = $pendingBillingService->enqueueMissingPeriodsUpToForCustomer($upToDate, $customerCariId);

        $monthLabel = $upToDate->locale('tr')->translatedFormat('F Y');

        return redirect()
            ->route('subscription-monitor.index', ['year' => $year, 'month' => $month])
            ->with('success', "{$monthLabel} için bu cariye ait eksik dönem siparişleri oluşturuldu. {$added} kayıt eklendi.");
    }
}

