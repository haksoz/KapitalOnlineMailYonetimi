<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('expense_settlements', function (Blueprint $table) {
            $table->boolean('is_closed')->default(false)->after('notes');
            $table->timestamp('closed_at')->nullable()->after('is_closed');

            $table->index('is_closed');
        });
    }

    public function down(): void
    {
        Schema::table('expense_settlements', function (Blueprint $table) {
            $table->dropIndex(['is_closed']);
            $table->dropColumn(['is_closed', 'closed_at']);
        });
    }
};
