<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pending_billings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('subscription_id')->constrained()->cascadeOnDelete();
            $table->date('period_start');
            $table->date('period_end')->nullable();
            $table->string('status', 32)->default('pending')->index();
            $table->timestamps();

            $table->unique(['subscription_id', 'period_start'], 'pending_billings_subscription_period_unique');
            $table->index('period_start');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pending_billings');
    }
};
