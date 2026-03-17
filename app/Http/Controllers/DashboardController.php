<?php

namespace App\Http\Controllers;

use App\Models\PendingBilling;
use App\Models\SalesInvoice;
use App\Models\Subscription;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        $today = Carbon::today();
        $year = (int) $today->year;
        $yearStart = Carbon::create($year, 1, 1)->startOfDay();
        $yearEnd = $yearStart->copy()->endOfYear();

        // 1) Yıl geneli: toplam alış (KDV hariç)
        // Dönem yılı 2026 olan ve alış faturası girilmiş siparişlerin (actual_alis_tl) toplamı
        $basePurchaseQuery = PendingBilling::query()
            ->whereNotNull('actual_alis_tl')
            ->whereYear('period_start', $year)
            ->where(function ($q) {
                $q->whereNotNull('supplier_invoice_number')
                    ->orWhereNotNull('supplier_invoice_date')
                    ->orWhereNotNull('actual_alis_tl');
            });

        $purchasesInvoicedYear = (float) (clone $basePurchaseQuery)
            ->where('status', PendingBilling::STATUS_INVOICED)
            ->sum('actual_alis_tl');

        // Bekleyen + ertelenen siparişler: henüz kesinleşmemiş alışlar
        $purchasesPendingYear = (float) (clone $basePurchaseQuery)
            ->whereIn('status', [PendingBilling::STATUS_PENDING, PendingBilling::STATUS_POSTPONED])
            ->sum('actual_alis_tl');

        $totalPurchasesYear = $purchasesInvoicedYear + $purchasesPendingYear;

        $invoicesForYear = SalesInvoice::query()
            ->whereBetween('our_invoice_date', [$yearStart->toDateString(), $yearEnd->toDateString()])
            ->get();

        // Sistem toplamı: siparişlerden hesaplanan fatura tutarları
        $totalSalesYearSystem = (float) $invoicesForYear->sum(fn (SalesInvoice $inv) => $inv->total_amount_tl ?? 0);

        // Kullanıcının girdiği fatura toplamı (varsa onu, yoksa sistem toplamını baz al)
        $totalSalesYearReported = (float) $invoicesForYear->sum(function (SalesInvoice $inv) {
            if ($inv->invoice_total_net_tl !== null) {
                return (float) $inv->invoice_total_net_tl;
            }

            return $inv->total_amount_tl !== null ? (float) $inv->total_amount_tl : 0;
        });

        // Farkların toplamı (pozitif + negatif)
        $totalSalesDiffYear = (float) $invoicesForYear->sum(fn (SalesInvoice $inv) => $inv->invoice_total_diff_tl ?? 0);

        // Bilanço: gerçek (kestiğin) faturalar üzerinden satış − alış
        $yearBalance = $totalSalesYearReported - $totalPurchasesYear;

        // 2) Abonelik / abone sayıları
        $activeSubscriptionsCount = Subscription::query()
            ->where('durum', Subscription::DURUM_ACTIVE)
            ->count();

        $activeCustomerCount = Subscription::query()
            ->where('durum', Subscription::DURUM_ACTIVE)
            ->distinct('customer_cari_id')
            ->count('customer_cari_id');

        // 3) Bekleyen sipariş sayısı (genel) — ertelenenler dahil
        $pendingBillingsCount = PendingBilling::query()
            ->whereIn('status', [PendingBilling::STATUS_PENDING, PendingBilling::STATUS_POSTPONED])
            ->count();

        // 4) Önceki ay ve bu ay görünümü (beklenen ve kesinleşen satış/alış)
        $monthStart = $today->copy()->startOfMonth();
        $monthEnd = $today->copy()->endOfMonth();

        $prevMonthStart = $monthStart->copy()->subMonthNoOverflow();
        $prevMonthEnd = $prevMonthStart->copy()->endOfMonth();

        // Beklenen satış/alış: period_start üzerinden (beklemede + ertelenen siparişler)
        $prevMonthPending = PendingBilling::query()
            ->whereIn('status', [PendingBilling::STATUS_PENDING, PendingBilling::STATUS_POSTPONED])
            ->whereBetween('period_start', [$prevMonthStart->toDateString(), $prevMonthEnd->toDateString()])
            ->get();
        $thisMonthPending = PendingBilling::query()
            ->whereIn('status', [PendingBilling::STATUS_PENDING, PendingBilling::STATUS_POSTPONED])
            ->whereBetween('period_start', [$monthStart->toDateString(), $monthEnd->toDateString()])
            ->get();

        $prevMonthExpectedSales = (float) $prevMonthPending->sum(fn (PendingBilling $pb) => $pb->expected_satis_tl ?? 0);
        $prevMonthExpectedPurchases = (float) $prevMonthPending->sum(fn (PendingBilling $pb) => $pb->expected_alis_tl ?? 0);

        $thisMonthExpectedSales = (float) $thisMonthPending->sum(fn (PendingBilling $pb) => $pb->expected_satis_tl ?? 0);
        $thisMonthExpectedPurchases = (float) $thisMonthPending->sum(fn (PendingBilling $pb) => $pb->expected_alis_tl ?? 0);

        // Kesinleşen satış: SalesInvoice.our_invoice_date üzerinden
        $prevMonthActualSales = (float) SalesInvoice::query()
            ->whereBetween('our_invoice_date', [$prevMonthStart->toDateString(), $prevMonthEnd->toDateString()])
            ->sum('total_amount_tl');
        $thisMonthActualSales = (float) SalesInvoice::query()
            ->whereBetween('our_invoice_date', [$monthStart->toDateString(), $monthEnd->toDateString()])
            ->sum('total_amount_tl');

        // Kesinleşen alış: PendingBilling.supplier_invoice_date üzerinden
        $prevMonthActualPurchases = (float) PendingBilling::query()
            ->whereNotNull('actual_alis_tl')
            ->whereBetween('supplier_invoice_date', [$prevMonthStart->toDateString(), $prevMonthEnd->toDateString()])
            ->sum('actual_alis_tl');
        $thisMonthActualPurchases = (float) PendingBilling::query()
            ->whereNotNull('actual_alis_tl')
            ->whereBetween('supplier_invoice_date', [$monthStart->toDateString(), $monthEnd->toDateString()])
            ->sum('actual_alis_tl');

        // Ay bazında bilanço: kesinleşen satış − kesinleşen alış
        $prevMonthActualBalance = $prevMonthActualSales - $prevMonthActualPurchases;
        $thisMonthActualBalance = $thisMonthActualSales - $thisMonthActualPurchases;

        $stats = [
            'year' => $year,
            'total_purchases_year' => $totalPurchasesYear,
            'purchases_invoiced_year' => $purchasesInvoicedYear,
            'purchases_pending_year' => $purchasesPendingYear,
            'total_sales_year' => $totalSalesYearReported,
            'total_sales_year_system' => $totalSalesYearSystem,
            'total_sales_diff_year' => $totalSalesDiffYear,
            'year_balance' => $yearBalance,
            'active_subscriptions_count' => $activeSubscriptionsCount,
            'active_customer_count' => $activeCustomerCount,
            'pending_billings_count' => $pendingBillingsCount,
            'prev_month_label' => $prevMonthStart->locale('tr')->translatedFormat('F Y'),
            'prev_month_expected_sales' => $prevMonthExpectedSales,
            'prev_month_expected_purchases' => $prevMonthExpectedPurchases,
            'prev_month_actual_sales' => $prevMonthActualSales,
            'prev_month_actual_purchases' => $prevMonthActualPurchases,
            'prev_month_actual_balance' => $prevMonthActualBalance,
            'this_month_label' => $monthStart->locale('tr')->translatedFormat('F Y'),
            'this_month_expected_sales' => $thisMonthExpectedSales,
            'this_month_expected_purchases' => $thisMonthExpectedPurchases,
            'this_month_actual_sales' => $thisMonthActualSales,
            'this_month_actual_purchases' => $thisMonthActualPurchases,
            'this_month_actual_balance' => $thisMonthActualBalance,
        ];

        return view('dashboard', [
            'stats' => $stats,
        ]);
    }
}

