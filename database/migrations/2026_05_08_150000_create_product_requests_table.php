<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cari_id')->constrained('caris')->cascadeOnDelete();
            $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete();
            $table->string('product_name')->nullable()->comment('Ürün yoksa manuel girilebilir');
            $table->text('description')->nullable();
            $table->string('status')->default('pending')->comment('pending, approved, rejected, completed');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['cari_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_requests');
    }
};
