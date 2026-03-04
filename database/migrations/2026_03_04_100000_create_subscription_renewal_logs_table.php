<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subscription_renewal_logs', function (Blueprint $table) {
            $table->id();
            $table->dateTime('run_at');
            $table->date('as_of_date')->nullable();
            $table->unsignedInteger('renewed_count')->default(0);
            $table->json('renewed_ids')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subscription_renewal_logs');
    }
};
