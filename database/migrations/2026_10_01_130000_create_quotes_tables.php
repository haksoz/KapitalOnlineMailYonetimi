<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quotes', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('quote_number', 16)->unique();
            $table->foreignId('customer_cari_id')->constrained('caris')->restrictOnDelete();
            $table->string('type', 32)->index();
            $table->string('status', 32)->default('draft')->index();
            $table->string('currency', 3);
            $table->decimal('vat_rate', 5, 2)->nullable();
            $table->date('valid_until')->nullable();
            $table->text('notes')->nullable();
            $table->text('internal_notes')->nullable();
            $table->foreignId('source_quote_id')->nullable()->unique()->constrained('quotes')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['customer_cari_id', 'status']);
        });

        Schema::create('quote_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('quote_id')->constrained('quotes')->cascadeOnDelete();
            $table->foreignId('product_id')->nullable()->constrained('products')->nullOnDelete();
            $table->string('product_name');
            $table->string('stock_code')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->unsignedInteger('quantity');
            $table->string('taahhut_tipi', 32)->nullable();
            $table->decimal('birim_alis', 12, 4)->nullable();
            $table->decimal('birim_satis', 12, 4)->nullable();
            $table->timestamps();

            $table->index('product_id');
        });

        Schema::table('quote_items', function (Blueprint $table) {
            $table->foreignId('source_quote_item_id')->nullable()->after('sort_order')->constrained('quote_items')->nullOnDelete();
        });

        Schema::create('quote_item_options', function (Blueprint $table) {
            $table->id();
            $table->foreignId('quote_item_id')->constrained('quote_items')->cascadeOnDelete();
            $table->string('taahhut_tipi', 32);
            $table->decimal('birim_alis', 12, 4)->nullable();
            $table->decimal('birim_satis', 12, 4);
            $table->timestamps();

            $table->unique(['quote_item_id', 'taahhut_tipi']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quote_item_options');
        Schema::table('quote_items', function (Blueprint $table) {
            $table->dropConstrainedForeignId('source_quote_item_id');
        });
        Schema::dropIfExists('quote_items');
        Schema::dropIfExists('quotes');
    }
};
