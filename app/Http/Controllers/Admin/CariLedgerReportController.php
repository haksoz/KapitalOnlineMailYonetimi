<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Cari;
use App\Models\PendingBilling;
use App\Models\Product;
use App\Models\Subscription;
use App\Services\AdminCariLedgerReportService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;

class CariLedgerReportController extends Controller
{
    public function index(Request $request, AdminCariLedgerReportService $service): View
    {
        $filters = $this->extractFilters($request);
        // Sayfa girişinde tüm veriyi çekmeyelim: tabloyu "Getir" ile dolduracağız.
        $getData = $request->boolean('get_data');
        $report = $getData
            ? $service->build($filters, includeGroupedTotals: false, includeGrandTotals: false)
            : ['rows' => collect()];

        $caris = Cari::query()
            ->whereIn('cari_type', ['customer', 'both'])
            ->orderBy('name')
            ->get(['id', 'name', 'short_name']);

        $selectedCariId = $filters['cari_id'] ?? null;

        $subscriptionBaseQuery = Subscription::query();
        if ($selectedCariId !== null && $selectedCariId !== '') {
            $subscriptionBaseQuery->where('customer_cari_id', (int) $selectedCariId);
        }

        $productIds = (clone $subscriptionBaseQuery)
            ->whereNotNull('product_id')
            ->pluck('product_id')
            ->unique()
            ->values()
            ->all();

        $products = Product::query()
            ->when($productIds !== [], fn ($q) => $q->whereIn('id', $productIds))
            ->orderBy('name')
            ->get(['id', 'name']);

        $contractNumbers = (clone $subscriptionBaseQuery)
            ->whereNotNull('sozlesme_no')
            ->where('sozlesme_no', '!=', '')
            ->orderBy('sozlesme_no')
            ->pluck('sozlesme_no')
            ->unique()
            ->values();

        return view('admin.reports.cari-ledger', [
            'report' => $report,
            'filters' => $filters,
            'caris' => $caris,
            'products' => $products,
            'contractNumbers' => $contractNumbers,
            'availableStatuses' => $this->availableStatuses(),
        ]);
    }

    public function export(Request $request, AdminCariLedgerReportService $service): Response
    {
        $filters = $this->extractFilters($request);
        $report = $service->build($filters, includeGroupedTotals: false, includeGrandTotals: true);
        $filename = 'cari-hesap-dokumu-' . now()->format('Ymd-His') . '.xls';

        $html = view('admin.reports.cari-ledger-export', [
            'report' => $report,
            'filters' => $filters,
        ])->render();

        return response($html, 200, [
            'Content-Type' => 'application/vnd.ms-excel; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }

    public function data(Request $request, AdminCariLedgerReportService $service): JsonResponse
    {
        $filters = $this->extractFilters($request);
        $report = $service->build($filters);

        return response()->json([
            'rows' => $report['rows'],
        ]);
    }

    public function totals(Request $request, AdminCariLedgerReportService $service): JsonResponse
    {
        $filters = $this->extractFilters($request);
        $totals = $service->calculateGrandTotals($filters);

        return response()->json([
            'expected_alis_tl' => (float) ($totals['beklenen_alis_tl'] ?? 0),
            'actual_alis_tl' => (float) ($totals['gerceklesen_alis_tl'] ?? 0),
            'fark_alis_tl' => (float) ($totals['fark_alis_tl'] ?? 0),
            'expected_satis_tl' => (float) ($totals['beklenen_satis_tl'] ?? 0),
            'actual_satis_tl' => (float) ($totals['gerceklesen_satis_tl'] ?? 0),
            'fark_satis_tl' => (float) ($totals['fark_satis_tl'] ?? 0),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function extractFilters(Request $request): array
    {
        $validated = $request->validate([
            'cari_id' => ['nullable', 'integer', 'exists:caris,id'],
            'product_id' => ['nullable', 'integer', 'exists:products,id'],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date'],
            'period_year' => ['nullable', 'integer', 'min:2000', 'max:2100'],
            'period_month' => ['nullable', 'integer', 'min:1', 'max:12'],
            'movement_type' => ['nullable', 'string', 'in:alis,satis'],
            'contract_no' => ['nullable', 'string', 'max:100'],
            'statuses' => ['nullable', 'array'],
            'statuses.*' => ['string', 'in:pending,postponed,invoiced,cancelled'],
        ]);

        $periodYear = $validated['period_year'] ?? null;
        $periodMonth = $validated['period_month'] ?? null;

        // Pending-billings mantığı: yıl/ay seçildiyse tarih aralığı (from/to) yok sayılsın.
        $from = $validated['from'] ?? null;
        $to = $validated['to'] ?? null;
        if (! empty($periodYear) || ! empty($periodMonth)) {
            $from = null;
            $to = null;
        }

        $statuses = $validated['statuses'] ?? [
            PendingBilling::STATUS_PENDING,
            PendingBilling::STATUS_POSTPONED,
            PendingBilling::STATUS_INVOICED,
        ];

        return [
            'cari_id' => $validated['cari_id'] ?? null,
            'product_id' => $validated['product_id'] ?? null,
            'from' => $from,
            'to' => $to,
            'period_year' => $periodYear,
            'period_month' => $periodMonth,
            'movement_type' => $validated['movement_type'] ?? null,
            'contract_no' => isset($validated['contract_no']) ? trim((string) $validated['contract_no']) : null,
            'statuses' => $statuses,
        ];
    }

    /**
     * @return array<string, string>
     */
    private function availableStatuses(): array
    {
        return [
            PendingBilling::STATUS_PENDING => 'Beklemede',
            PendingBilling::STATUS_POSTPONED => 'Ertelendi',
            PendingBilling::STATUS_INVOICED => 'Faturalandı',
            PendingBilling::STATUS_CANCELLED => 'İptal',
        ];
    }
}
