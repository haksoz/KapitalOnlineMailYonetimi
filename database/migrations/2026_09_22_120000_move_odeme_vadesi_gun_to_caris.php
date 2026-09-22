<?php

use Carbon\Carbon;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('caris', 'odeme_vadesi_gun')) {
            Schema::table('caris', function (Blueprint $table) {
                $table->unsignedInteger('odeme_vadesi_gun')->nullable()->after('cari_type');
            });
        }

        if (Schema::hasColumn('subscriptions', 'odeme_vadesi_gun')) {
            $terms = DB::table('subscriptions')
                ->whereNotNull('odeme_vadesi_gun')
                ->whereNotNull('customer_cari_id')
                ->selectRaw('customer_cari_id, MIN(odeme_vadesi_gun) as days')
                ->groupBy('customer_cari_id')
                ->get();

            foreach ($terms as $row) {
                DB::table('caris')
                    ->where('id', $row->customer_cari_id)
                    ->whereNull('odeme_vadesi_gun')
                    ->update(['odeme_vadesi_gun' => (int) $row->days]);
            }
        }

        if (! Schema::hasColumn('expense_settlements', 'due_date')) {
            Schema::table('expense_settlements', function (Blueprint $table) {
                $table->date('due_date')->nullable()->after('settlement_date');
                $table->index('due_date');
            });
        }

        $this->backfillDocumentDueDates();

        if (Schema::hasColumn('subscriptions', 'odeme_vadesi_gun')) {
            Schema::table('subscriptions', function (Blueprint $table) {
                $table->dropColumn('odeme_vadesi_gun');
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasColumn('subscriptions', 'odeme_vadesi_gun')) {
            Schema::table('subscriptions', function (Blueprint $table) {
                $table->unsignedInteger('odeme_vadesi_gun')->nullable()->after('vat_rate');
            });
        }

        if (Schema::hasColumn('caris', 'odeme_vadesi_gun')) {
            $terms = DB::table('caris')
                ->whereNotNull('odeme_vadesi_gun')
                ->select(['id', 'odeme_vadesi_gun'])
                ->get();

            foreach ($terms as $row) {
                DB::table('subscriptions')
                    ->where('customer_cari_id', $row->id)
                    ->whereNull('odeme_vadesi_gun')
                    ->update(['odeme_vadesi_gun' => (int) $row->odeme_vadesi_gun]);
            }

            Schema::table('caris', function (Blueprint $table) {
                $table->dropColumn('odeme_vadesi_gun');
            });
        }

        if (Schema::hasColumn('expense_settlements', 'due_date')) {
            Schema::table('expense_settlements', function (Blueprint $table) {
                $table->dropIndex(['due_date']);
                $table->dropColumn('due_date');
            });
        }
    }

    private function backfillDocumentDueDates(): void
    {
        $invoices = DB::table('sales_invoices as si')
            ->join('caris as c', 'c.id', '=', 'si.customer_cari_id')
            ->whereNull('si.due_date')
            ->whereNotNull('c.odeme_vadesi_gun')
            ->whereNotNull('si.our_invoice_date')
            ->whereNotNull('si.our_invoice_number')
            ->where('si.our_invoice_number', '!=', '')
            ->select(['si.id', 'si.our_invoice_date', 'c.odeme_vadesi_gun'])
            ->get();

        foreach ($invoices as $row) {
            DB::table('sales_invoices')->where('id', $row->id)->update([
                'due_date' => Carbon::parse($row->our_invoice_date)
                    ->addDays((int) $row->odeme_vadesi_gun)
                    ->toDateString(),
            ]);
        }

        $settlements = DB::table('expense_settlements as e')
            ->join('caris as c', 'c.id', '=', 'e.customer_cari_id')
            ->whereNull('e.due_date')
            ->whereNotNull('c.odeme_vadesi_gun')
            ->whereNotNull('e.settlement_date')
            ->select(['e.id', 'e.settlement_date', 'c.odeme_vadesi_gun'])
            ->get();

        foreach ($settlements as $row) {
            DB::table('expense_settlements')->where('id', $row->id)->update([
                'due_date' => Carbon::parse($row->settlement_date)
                    ->addDays((int) $row->odeme_vadesi_gun)
                    ->toDateString(),
            ]);
        }
    }
};
