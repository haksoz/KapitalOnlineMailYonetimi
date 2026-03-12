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

        // 1) Yıl geneli: toplam alış ve satış (KDV hariç)
        $totalPurchasesYear = (float) PendingBilling::query()
            ->whereNotNull('actual_alis_tl')
            ->whereBetween('supplier_invoice_date', [$yearStart->toDateString(), $yearEnd->toDateString()])
            ->sum('actual_alis_tl');

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

        // 3) Bekleyen sipariş sayısı (genel)
        $pendingBillingsCount = PendingBilling::query()
            ->where('status', PendingBilling::STATUS_PENDING)
            ->count();

        // 4) Bu ay ve gelecek ay görünümü (beklenen satış/alış)
        $monthStart = $today->copy()->startOfMonth();
        $monthEnd = $today->copy()->endOfMonth();

        $nextMonthStart = $monthStart->copy()->addMonthNoOverflow();
        $nextMonthEnd = $nextMonthStart->copy()->endOfMonth();

        $thisMonthPending = PendingBilling::query()
            ->whereBetween('period_start', [$monthStart->toDateString(), $monthEnd->toDateString()])
            ->get();

        $nextMonthPending = PendingBilling::query()
            ->whereBetween('period_start', [$nextMonthStart->toDateString(), $nextMonthEnd->toDateString()])
            ->get();

        $thisMonthExpectedSales = (float) $thisMonthPending->sum(fn (PendingBilling $pb) => $pb->expected_satis_tl ?? 0);
        $thisMonthExpectedPurchases = (float) $thisMonthPending->sum(fn (PendingBilling $pb) => $pb->expected_alis_tl ?? 0);
        $thisMonthBalance = $thisMonthExpectedSales - $thisMonthExpectedPurchases;

        $nextMonthExpectedSales = (float) $nextMonthPending->sum(fn (PendingBilling $pb) => $pb->expected_satis_tl ?? 0);
        $nextMonthExpectedPurchases = (float) $nextMonthPending->sum(fn (PendingBilling $pb) => $pb->expected_alis_tl ?? 0);
        $nextMonthBalance = $nextMonthExpectedSales - $nextMonthExpectedPurchases;

        $stats = [
            'year' => $year,
            'total_purchases_year' => $totalPurchasesYear,
            'total_sales_year' => $totalSalesYearReported,
            'total_sales_year_system' => $totalSalesYearSystem,
            'total_sales_diff_year' => $totalSalesDiffYear,
            'year_balance' => $yearBalance,
            'active_subscriptions_count' => $activeSubscriptionsCount,
            'active_customer_count' => $activeCustomerCount,
            'pending_billings_count' => $pendingBillingsCount,
            'this_month_label' => $monthStart->locale('tr')->translatedFormat('F Y'),
            'this_month_expected_sales' => $thisMonthExpectedSales,
            'this_month_expected_purchases' => $thisMonthExpectedPurchases,
            'this_month_balance' => $thisMonthBalance,
            'next_month_label' => $nextMonthStart->locale('tr')->translatedFormat('F Y'),
            'next_month_expected_sales' => $nextMonthExpectedSales,
            'next_month_expected_purchases' => $nextMonthExpectedPurchases,
            'next_month_balance' => $nextMonthBalance,
        ];

        return view('dashboard', [
            'stats' => $stats,
        ]);
    }
}

