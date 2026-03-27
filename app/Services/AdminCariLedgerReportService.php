<?php

namespace App\Services;

use App\Models\PendingBilling;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class AdminCariLedgerReportService
{
    /**
     * Filtrelere göre yalnızca genel (grand) finansal toplamları hesaplar.
     *
     * @param  array<string, mixed>  $filters
     * @return array<string, float>
     */
    public function calculateGrandTotals(array $filters): array
    {
        $report = $this->build($filters, includeGroupedTotals: false, includeGrandTotals: true);

        return $report['grandTotals'];
    }

    /**
     * @param  array<string, mixed>  $filters
     * @param  bool  $includeGroupedTotals  cari bazlı dip toplamlar hesaplansın mı?
     * @param  bool  $includeGrandTotals    genel toplamlar hesaplansın mı?
     * @return array{
     *   rows: \Illuminate\Support\Collection<int, array<string, mixed>>,
     *   groupedTotals: array<int, array<string, float|string|null>>,
     *   grandTotals: array<string, float>,
     *   meta: array<string, mixed>
     * }
     */
    public function build(array $filters, bool $includeGroupedTotals = false, bool $includeGrandTotals = false): array
    {
        $pendingBillings = $this->buildBaseQuery($filters)->get();

        $rows = collect();
        foreach ($pendingBillings as $pendingBilling) {
            $rows->push($this->makeAlisRow($pendingBilling));
            $rows->push($this->makeSatisRow($pendingBilling));
        }

        $movementType = $filters['movement_type'] ?? null;
        if (in_array($movementType, ['alis', 'satis'], true)) {
            $rows = $rows
                ->filter(fn (array $row) => ($row['hareket_tipi'] ?? null) === $movementType)
                ->values();
        }

        $rows = $rows->sortBy([
            ['islem_tarihi_sort', 'asc'],
            ['cari_unvan', 'asc'],
            ['period_start', 'asc'],
            ['hareket_tipi_order', 'asc'],
            ['pending_billing_id', 'asc'],
        ])->values();

        $groupedTotals = [];
        if ($includeGroupedTotals) {
            $groupedRows = $rows->groupBy('cari_unvan');
            foreach ($groupedRows as $cariName => $cariRows) {
                $groupedTotals[] = $this->calculateTotalsForGroup($cariRows, (string) $cariName);
            }
        }

        $grandTotals = [];
        if ($includeGrandTotals) {
            $grandTotals = $this->calculateGrandTotalsFromRows($rows);
        }

        return [
            'rows' => $rows,
            'groupedTotals' => $groupedTotals,
            'grandTotals' => $grandTotals,
            'meta' => [
                'row_count' => $rows->count(),
                'record_count' => $pendingBillings->count(),
                'filters' => $filters,
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    private function buildBaseQuery(array $filters): Builder
    {
        $query = PendingBilling::query()
            ->with([
                'subscription.customerCari',
                'subscription.product',
                'salesInvoiceLine.salesInvoice',
            ]);

        $statuses = $filters['statuses'] ?? [
            PendingBilling::STATUS_PENDING,
            PendingBilling::STATUS_POSTPONED,
            PendingBilling::STATUS_INVOICED,
        ];
        if (is_array($statuses) && $statuses !== []) {
            $query->whereIn('status', $statuses);
        }

        $cariId = $filters['cari_id'] ?? null;
        if ($cariId !== null && $cariId !== '') {
            $query->whereHas('subscription', function (Builder $builder) use ($cariId): void {
                $builder->where('customer_cari_id', (int) $cariId);
            });
        }

        $productId = $filters['product_id'] ?? null;
        if ($productId !== null && $productId !== '') {
            $query->whereHas('subscription', function (Builder $builder) use ($productId): void {
                $builder->where('product_id', (int) $productId);
            });
        }

        $contractNo = $filters['contract_no'] ?? null;
        if ($contractNo !== null && $contractNo !== '') {
            $query->whereHas('subscription', function (Builder $builder) use ($contractNo): void {
                $builder->where('sozlesme_no', 'like', '%' . $contractNo . '%');
            });
        }

        $from = $filters['from'] ?? null;
        if ($from) {
            $query->whereDate('period_start', '>=', $from);
        }

        $to = $filters['to'] ?? null;
        if ($to) {
            $query->whereDate('period_start', '<=', $to);
        }

        $periodYear = $filters['period_year'] ?? null;
        if ($periodYear) {
            $query->whereYear('period_start', (int) $periodYear);
        }

        $periodMonth = $filters['period_month'] ?? null;
        if ($periodMonth) {
            $query->whereMonth('period_start', (int) $periodMonth);
        }

        return $query;
    }

    /**
     * @return array<string, mixed>
     */
    private function makeAlisRow(PendingBilling $pendingBilling): array
    {
        $expected = $this->toFloatOrZero($pendingBilling->expected_alis_tl);
        $actual = $this->toFloatOrZero($pendingBilling->actual_alis_tl);
        $supplierInvoiceDate = $pendingBilling->supplier_invoice_date?->format('Y-m-d');

        return [
            'cari_id' => $pendingBilling->subscription->customerCari?->id,
            'cari_unvan' => $pendingBilling->subscription->customerCari?->short_name
                ?: $pendingBilling->subscription->customerCari?->name
                ?: 'Tanımsız Cari',
            'pending_billing_id' => $pendingBilling->id,
            'period_start' => $pendingBilling->period_start?->format('Y-m-d'),
            'donem' => $pendingBilling->period_start?->format('m/Y'),
            'durum' => $pendingBilling->status,
            'sozlesme_no' => $pendingBilling->subscription->sozlesme_no,
            'urun_adi' => $pendingBilling->subscription->product?->name,
            'hareket_tipi' => 'alis',
            'hareket_tipi_label' => 'Alış',
            'hareket_tipi_order' => 1,
            'islem_tarihi' => $supplierInvoiceDate ?: $pendingBilling->period_start?->format('Y-m-d'),
            'islem_tarihi_sort' => $supplierInvoiceDate ?: $pendingBilling->period_start?->format('Y-m-d'),
            'beklenen_alis_tl' => $expected,
            'gerceklesen_alis_tl' => $actual,
            'fark_alis_tl' => $actual - $expected,
            'beklenen_satis_tl' => 0.0,
            'gerceklesen_satis_tl' => 0.0,
            'fark_satis_tl' => 0.0,
            'alis_fatura_no' => $pendingBilling->supplier_invoice_number,
            'alis_fatura_tarihi' => $supplierInvoiceDate,
            'satis_fatura_no' => null,
            'satis_fatura_tarihi' => null,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function makeSatisRow(PendingBilling $pendingBilling): array
    {
        $expected = $this->toFloatOrZero($pendingBilling->expected_satis_tl);
        $actual = $this->toFloatOrZero($pendingBilling->actual_satis_tl);
        $salesInvoice = $pendingBilling->salesInvoiceLine?->salesInvoice;
        $salesInvoiceDate = $salesInvoice?->our_invoice_date?->format('Y-m-d');

        return [
            'cari_id' => $pendingBilling->subscription->customerCari?->id,
            'cari_unvan' => $pendingBilling->subscription->customerCari?->short_name
                ?: $pendingBilling->subscription->customerCari?->name
                ?: 'Tanımsız Cari',
            'pending_billing_id' => $pendingBilling->id,
            'period_start' => $pendingBilling->period_start?->format('Y-m-d'),
            'donem' => $pendingBilling->period_start?->format('m/Y'),
            'durum' => $pendingBilling->status,
            'sozlesme_no' => $pendingBilling->subscription->sozlesme_no,
            'urun_adi' => $pendingBilling->subscription->product?->name,
            'hareket_tipi' => 'satis',
            'hareket_tipi_label' => 'Satış',
            'hareket_tipi_order' => 2,
            'islem_tarihi' => $salesInvoiceDate ?: $pendingBilling->period_start?->format('Y-m-d'),
            'islem_tarihi_sort' => $salesInvoiceDate ?: $pendingBilling->period_start?->format('Y-m-d'),
            'beklenen_alis_tl' => 0.0,
            'gerceklesen_alis_tl' => 0.0,
            'fark_alis_tl' => 0.0,
            'beklenen_satis_tl' => $expected,
            'gerceklesen_satis_tl' => $actual,
            // Sipariş ekranı ile aynı kural: Satış Fark = Beklenen - Gerçekleşen
            'fark_satis_tl' => $expected - $actual,
            'alis_fatura_no' => null,
            'alis_fatura_tarihi' => null,
            'satis_fatura_no' => $salesInvoice?->our_invoice_number,
            'satis_fatura_tarihi' => $salesInvoiceDate,
        ];
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $rows
     * @return array<string, float|string|null>
     */
    private function calculateTotalsForGroup(Collection $rows, string $cariName): array
    {
        return [
            'cari_unvan' => $cariName,
            'beklenen_alis_tl' => (float) $rows->sum('beklenen_alis_tl'),
            'gerceklesen_alis_tl' => (float) $rows->sum('gerceklesen_alis_tl'),
            'fark_alis_tl' => (float) $rows->sum('fark_alis_tl'),
            'beklenen_satis_tl' => (float) $rows->sum('beklenen_satis_tl'),
            'gerceklesen_satis_tl' => (float) $rows->sum('gerceklesen_satis_tl'),
            'fark_satis_tl' => (float) $rows->sum('fark_satis_tl'),
        ];
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $rows
     * @return array<string, float>
     */
    private function calculateGrandTotalsFromRows(Collection $rows): array
    {
        return [
            'beklenen_alis_tl' => (float) $rows->sum('beklenen_alis_tl'),
            'gerceklesen_alis_tl' => (float) $rows->sum('gerceklesen_alis_tl'),
            'fark_alis_tl' => (float) $rows->sum('fark_alis_tl'),
            'beklenen_satis_tl' => (float) $rows->sum('beklenen_satis_tl'),
            'gerceklesen_satis_tl' => (float) $rows->sum('gerceklesen_satis_tl'),
            'fark_satis_tl' => (float) $rows->sum('fark_satis_tl'),
        ];
    }

    private function toFloatOrZero(mixed $value): float
    {
        if ($value === null || $value === '') {
            return 0.0;
        }

        return (float) $value;
    }
}
