<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subscription_monthly_projections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('subscription_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('year');
            $table->unsignedTinyInteger('month');
            $table->unsignedInteger('expected_quantity')->default(1);
            $table->decimal('expected_unit_cost_usd', 12, 4)->default(0);
            $table->decimal('expected_total_usd', 14, 4)->default(0);
            $table->decimal('estimated_exchange_rate', 14, 6)->default(1);
            $table->decimal('expected_total_try', 16, 2)->default(0);
            $table->decimal('actual_total_usd', 14, 4)->nullable();
            $table->decimal('actual_total_try', 16, 2)->nullable();
            $table->decimal('difference_usd', 14, 4)->nullable();
            $table->decimal('difference_try', 16, 2)->nullable();
            $table->string('status', 32)->default('projected');
            $table->timestamps();

            $table->unique(['subscription_id', 'year', 'month'], 'sub_monthly_proj_sub_year_month_unique');
            $table->index(['year', 'month']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subscription_monthly_projections');
    }
};
