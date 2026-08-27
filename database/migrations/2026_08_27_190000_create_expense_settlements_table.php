<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('expense_settlements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_cari_id')->constrained('caris')->cascadeOnDelete();
            $table->string('gider_number', 64)->unique();
            $table->date('settlement_date');
            $table->decimal('total_amount_tl', 14, 2)->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index('customer_cari_id');
            $table->index('settlement_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('expense_settlements');
    }
};
