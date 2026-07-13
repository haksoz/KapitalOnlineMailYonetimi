<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_price_history', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->string('field_name', 64);
            $table->decimal('old_value', 14, 4)->nullable();
            $table->decimal('new_value', 14, 4)->nullable();
            $table->foreignId('changed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('reason')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index('product_id', 'pph_product_id');
            $table->index(['product_id', 'created_at'], 'pph_product_created');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_price_history');
    }
};
