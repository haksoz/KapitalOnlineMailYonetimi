<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('expense_settlement_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('expense_settlement_id')->constrained()->cascadeOnDelete();
            $table->foreignId('pending_billing_id')->constrained()->cascadeOnDelete();
            $table->decimal('line_amount_tl', 14, 2);
            $table->timestamps();

            $table->unique('pending_billing_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('expense_settlement_lines');
    }
};
