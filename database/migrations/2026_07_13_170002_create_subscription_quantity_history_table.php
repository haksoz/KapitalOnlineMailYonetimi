<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subscription_quantity_history', function (Blueprint $table) {
            $table->id();
            $table->foreignId('subscription_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('previous_quantity');
            $table->unsignedInteger('new_quantity');
            $table->date('effective_date');
            $table->foreignId('changed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('reason')->nullable();
            $table->timestamps();

            $table->index('subscription_id', 'sqh_subscription_id');
            $table->index(['subscription_id', 'effective_date'], 'sqh_subscription_effective');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subscription_quantity_history');
    }
};
