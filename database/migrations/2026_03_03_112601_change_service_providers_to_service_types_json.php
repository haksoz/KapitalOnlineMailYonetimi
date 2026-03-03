<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('service_providers', function (Blueprint $table) {
            $table->json('service_types')->nullable()->after('code');
        });

        // Mevcut service_type değerini service_types array'e taşı
        $rows = DB::table('service_providers')->whereNotNull('service_type')->get();
        foreach ($rows as $row) {
            DB::table('service_providers')
                ->where('id', $row->id)
                ->update(['service_types' => json_encode([$row->service_type])]);
        }

        Schema::table('service_providers', function (Blueprint $table) {
            $table->dropColumn('service_type');
        });
    }

    public function down(): void
    {
        Schema::table('service_providers', function (Blueprint $table) {
            $table->string('service_type', 50)->nullable()->after('code');
        });

        $rows = DB::table('service_providers')->get();
        foreach ($rows as $row) {
            $types = $row->service_types ? json_decode($row->service_types, true) : [];
            $first = is_array($types) && count($types) > 0 ? $types[0] : null;
            DB::table('service_providers')->where('id', $row->id)->update(['service_type' => $first]);
        }

        Schema::table('service_providers', function (Blueprint $table) {
            $table->dropColumn('service_types');
        });
    }
};
