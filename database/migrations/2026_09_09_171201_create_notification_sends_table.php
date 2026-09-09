<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notification_sends', function (Blueprint $table) {
            $table->id();
            $table->foreignId('notification_definition_id')->constrained('notification_definitions')->cascadeOnDelete();
            $table->foreignId('sales_invoice_id')->constrained('sales_invoices')->cascadeOnDelete();
            $table->timestamp('sent_at');
            $table->string('to_email', 255);
            $table->timestamps();

            $table->index(['notification_definition_id', 'sales_invoice_id', 'sent_at'], 'notification_sends_lookup_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notification_sends');
    }
};
