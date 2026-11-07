<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quote_work_task', function (Blueprint $table) {
            $table->foreignId('quote_id')->constrained()->cascadeOnDelete();
            $table->foreignId('work_task_id')->constrained()->cascadeOnDelete();
            $table->primary(['quote_id', 'work_task_id']);
        });
        Schema::table('quote_items', function (Blueprint $table) {
            $table->foreignId('work_task_id')->nullable()->constrained()->nullOnDelete();
            $table->json('renewal_context')->nullable();
            $table->string('response_status')->default('pending')->index();
            $table->text('response_note')->nullable();
            $table->timestamp('responded_at')->nullable();
            $table->foreignId('responded_by')->nullable()->constrained('users')->nullOnDelete();
        });
        DB::table('quotes')->orderBy('id')->chunkById(100, function ($quotes) {
            foreach ($quotes as $quote) {
                $response = match ($quote->status) {
                    'approved' => 'approved', 'rejected' => 'rejected', default => 'pending'
                };
                DB::table('quote_items')->where('quote_id', $quote->id)->update(['response_status' => $response]);
                if (! $quote->work_task_id) {
                    continue;
                }
                DB::table('quote_work_task')->insert(['quote_id' => $quote->id, 'work_task_id' => $quote->work_task_id]);
                $snapshot = json_decode($quote->renewal_context ?? 'null', true);
                if ($snapshot && isset($snapshot['product_id'])) {
                    DB::table('quote_items')->where('quote_id', $quote->id)->where('product_id', $snapshot['product_id'])
                        ->update(['work_task_id' => $quote->work_task_id, 'renewal_context' => $quote->renewal_context]);
                }
            }
        });
    }

    public function down(): void
    {
        Schema::table('quote_items', function (Blueprint $table) {
            $table->dropConstrainedForeignId('work_task_id');
            $table->dropConstrainedForeignId('responded_by');
            $table->dropIndex(['response_status']);
            $table->dropColumn(['renewal_context', 'response_status', 'response_note', 'responded_at']);
        });
        Schema::dropIfExists('quote_work_task');
    }
};
