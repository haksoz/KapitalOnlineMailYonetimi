<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notification_definitions', function (Blueprint $table) {
            $table->id();
            $table->string('key', 64)->unique();
            $table->string('name', 128);
            $table->boolean('is_enabled')->default(false);
            $table->unsignedInteger('start_after_days')->default(0);
            $table->unsignedInteger('interval_days')->default(7);
            $table->string('subject', 255);
            $table->text('body');
            $table->timestamps();
        });

        $now = now();
        DB::table('notification_definitions')->insert([
            [
                'key' => 'invoice_due_reminder',
                'name' => 'Vade öncesi hatırlatma',
                'is_enabled' => false,
                'start_after_days' => 0,
                'interval_days' => 7,
                'subject' => 'Fatura hatırlatması: {fatura_no}',
                'body' => "Sayın {musteri},\n\n{fatura_no} numaralı faturanızın vadesi {vade_tarihi} tarihine kadardır.\nTutar (KDV dahil): {tutar}\nFatura tarihi: {fatura_tarihi}\nTakip no: {ftn}\n\nÖdeme yaptıysanız bu iletiyi dikkate almayınız.",
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'key' => 'invoice_overdue',
                'name' => 'Vade sonrası gecikme',
                'is_enabled' => false,
                'start_after_days' => 0,
                'interval_days' => 3,
                'subject' => 'Geciken fatura: {fatura_no}',
                'body' => "Sayın {musteri},\n\n{fatura_no} numaralı faturanızın vadesi {vade_tarihi} tarihinde dolmuştur ve henüz ödenmemiştir.\nTutar (KDV dahil): {tutar}\nFatura tarihi: {fatura_tarihi}\nTakip no: {ftn}\n\nLütfen ödemeyi en kısa sürede tamamlayınız.",
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('notification_definitions');
    }
};
