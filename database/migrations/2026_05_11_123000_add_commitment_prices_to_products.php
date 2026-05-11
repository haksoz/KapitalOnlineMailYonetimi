<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            // Aylık Taahhütlü
            $table->decimal('alis_usd_monthly_commitment', 10, 2)->nullable()->after('default_satis_usd')->comment('Aylik taahhutlu alis USD');
            $table->decimal('satis_usd_monthly_commitment', 10, 2)->nullable()->after('alis_usd_monthly_commitment')->comment('Aylik taahhutlu satis USD');
            // Aylık Taahhütsüz
            $table->decimal('alis_usd_monthly_no_commitment', 10, 2)->nullable()->after('satis_usd_monthly_commitment')->comment('Aylik taahhuetsuez alis USD');
            $table->decimal('satis_usd_monthly_no_commitment', 10, 2)->nullable()->after('alis_usd_monthly_no_commitment')->comment('Aylik taahhuetsuez satis USD');
            // Yıllık Taahhütlü
            $table->decimal('alis_usd_yearly_commitment', 10, 2)->nullable()->after('satis_usd_monthly_no_commitment')->comment('Yillik taahhutlu alis USD');
            $table->decimal('satis_usd_yearly_commitment', 10, 2)->nullable()->after('alis_usd_yearly_commitment')->comment('Yillik taahhutlu satis USD');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn([
                'alis_usd_monthly_commitment',
                'satis_usd_monthly_commitment',
                'alis_usd_monthly_no_commitment',
                'satis_usd_monthly_no_commitment',
                'alis_usd_yearly_commitment',
                'satis_usd_yearly_commitment',
            ]);
        });
    }
};
