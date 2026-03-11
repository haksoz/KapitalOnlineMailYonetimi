<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mail_settings', function (Blueprint $table) {
            $table->id();
            $table->boolean('use_custom')->default(false)->comment('true = DB ayarları kullanılsın, false = .env/config kullanılsın');
            $table->string('driver', 32)->default('smtp')->comment('smtp, log');
            $table->string('host', 255)->nullable();
            $table->unsignedSmallInteger('port')->nullable();
            $table->string('username', 255)->nullable();
            $table->text('password')->nullable()->comment('Encrypted');
            $table->string('encryption', 16)->nullable()->comment('tls, ssl veya boş');
            $table->string('from_address', 255)->nullable();
            $table->string('from_name', 255)->nullable();
            $table->timestamps();
        });

        \DB::table('mail_settings')->insert([
            'use_custom' => false,
            'driver' => 'log',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('mail_settings');
    }
};
