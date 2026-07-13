<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subscription_price_history', function (Blueprint $table) {
            $table->id();
            $table->foreignId('subscription_id')->constrained()->cascadeOnDelete();
            $table->string('field_name', 32);
            $table->decimal('old_value', 14, 4)->nullable();
            $table->decimal('new_value', 14, 4)->nullable();
            $table->foreignId('changed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('reason')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index('subscription_id', 'sph_subscription_id');
            $table->index(['subscription_id', 'created_at'], 'sph_subscription_created');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subscription_price_history');
    }
};
