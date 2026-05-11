<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('api_integrations', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->boolean('is_active')->default(true);
            $table->string('base_url')->nullable();
            $table->string('api_version')->default('v1');
            $table->text('description')->nullable();
            $table->timestamp('last_accessed_at')->nullable();
            $table->timestamp('last_synced_at')->nullable();
            $table->timestamps();
        });

        Schema::create('api_keys', function (Blueprint $table) {
            $table->id();
            $table->foreignId('api_integration_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('token_hash');
            $table->string('token_prefix', 8);
            $table->enum('permission_level', ['read', 'write', 'admin'])->default('read');
            $table->text('description')->nullable();
            $table->string('allowed_ips')->nullable();
            $table->integer('rate_limit_per_minute')->default(60);
            $table->boolean('is_active')->default(true);
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('last_used_at')->nullable();
            $table->timestamp('revoked_at')->nullable();
            $table->timestamps();
        });

        Schema::create('webhook_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('api_integration_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('callback_url');
            $table->json('events')->nullable();
            $table->boolean('is_active')->default(false);
            $table->string('secret_hash')->nullable();
            $table->integer('retry_count')->default(3);
            $table->timestamp('last_triggered_at')->nullable();
            $table->timestamp('last_success_at')->nullable();
            $table->timestamps();
        });

        Schema::create('api_request_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('api_integration_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('api_key_id')->nullable()->constrained()->nullOnDelete();
            $table->string('method', 10);
            $table->string('endpoint');
            $table->integer('status_code')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->text('request_payload')->nullable();
            $table->text('response_summary')->nullable();
            $table->text('error_message')->nullable();
            $table->float('duration_ms')->nullable();
            $table->timestamp('requested_at');
            $table->timestamps();

            $table->index(['api_integration_id', 'requested_at']);
            $table->index('requested_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('api_request_logs');
        Schema::dropIfExists('webhook_settings');
        Schema::dropIfExists('api_keys');
        Schema::dropIfExists('api_integrations');
    }
};
