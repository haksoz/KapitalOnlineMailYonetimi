<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('work_tasks', function (Blueprint $table) {
            $table->id();
            $table->string('type')->index();
            $table->string('occurrence_key')->unique();
            $table->morphs('subject');
            $table->foreignId('customer_cari_id')->nullable()->constrained('caris')->nullOnDelete();
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->string('title');
            $table->string('status')->index();
            $table->date('due_date')->index();
            $table->date('next_follow_up')->nullable()->index();
            $table->json('snapshot');
            $table->decimal('verified_cost', 18, 4)->nullable();
            $table->decimal('proposed_sale', 18, 4)->nullable();
            $table->foreignId('cost_checked_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('cost_checked_at')->nullable();
            $table->timestamp('notified_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->text('result')->nullable();
            $table->timestamps();
        });
        Schema::create('work_task_activities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('work_task_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->text('description');
            $table->json('changes')->nullable();
            $table->timestamps();
        });
        Schema::table('quotes', function (Blueprint $table) {
            $table->foreignId('work_task_id')->nullable()->constrained()->nullOnDelete();
            $table->json('renewal_context')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('quotes', function (Blueprint $table) {
            $table->dropConstrainedForeignId('work_task_id');
            $table->dropColumn('renewal_context');
        });
        Schema::dropIfExists('work_task_activities');
        Schema::dropIfExists('work_tasks');
    }
};
