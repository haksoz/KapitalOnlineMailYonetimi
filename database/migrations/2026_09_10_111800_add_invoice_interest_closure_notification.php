<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $exists = DB::table('notification_definitions')
            ->where('key', 'invoice_interest_closure')
            ->exists();

        if ($exists) {
            return;
        }

        $now = now();
        DB::table('notification_definitions')->insert([
            'key' => 'invoice_interest_closure',
            'name' => 'Faiz Uygulaması ve Kapatma',
            'is_enabled' => false,
            'start_after_days' => 30,
            'interval_days' => 30,
            'send_at' => '16:00:00',
            'subject' => 'Yasal uyarı: faiz uygulaması ve kapatma — {fatura_no}',
            'body' => "Sayın {musteri},\n\n{fatura_no} numaralı faturanızın vadesi {vade_tarihi} tarihinde dolmuştur ve ödeme 1 ayı aşkın süredir gerçekleşmemiştir.\n\nTutar (KDV dahil): {tutar}\nFatura tarihi: {fatura_tarihi}\nTakip no: {ftn}\n\nBu aşamada gecikme faizi uygulanabilir ve hizmetlerinizin kapatılması söz konusu olabilir. Yasal takip sürecinin başlamaması için ödemeyi ivedilikle tamamlamanızı rica ederiz.\n\nÖdeme yaptıysanız bu iletiyi dikkate almayınız.",
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }

    public function down(): void
    {
        DB::table('notification_definitions')
            ->where('key', 'invoice_interest_closure')
            ->delete();
    }
};
