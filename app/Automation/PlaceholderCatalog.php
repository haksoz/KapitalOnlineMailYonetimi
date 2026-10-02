<?php

namespace App\Automation;

final class PlaceholderCatalog
{
    /**
     * @return list<array{group: string, items: list<array{token: string, hint: string}>}>
     */
    public static function groups(): array
    {
        return [
            [
                'group' => 'Müşteri',
                'items' => [
                    ['token' => '{musteri}', 'hint' => 'Cari kısa ad veya ünvan'],
                ],
            ],
            [
                'group' => 'Fatura',
                'items' => [
                    ['token' => '{fatura_no}', 'hint' => 'Kesilen fatura numarası'],
                    ['token' => '{fatura_tarihi}', 'hint' => 'Fatura tarihi'],
                    ['token' => '{vade_tarihi}', 'hint' => 'Ödeme vadesi'],
                    ['token' => '{tutar}', 'hint' => 'KDV dahil ödenecek toplam'],
                    ['token' => '{ftn}', 'hint' => 'Fatura takip no'],
                    ['token' => '{abonelikler}', 'hint' => 'Faturadaki abonelikler; her satır ayrı (ürün — sözleşme — adet)'],
                    ['token' => '{abonelik_no}', 'hint' => 'Faturadaki sözleşme no (birden fazlaysa virgülle)'],
                    ['token' => '{adet}', 'hint' => 'Faturadaki adetler (birden fazlaysa virgülle)'],
                ],
            ],
            [
                'group' => 'Abonelik',
                'items' => [
                    ['token' => '{abonelik_no}', 'hint' => 'Sözleşme numarası (abonelik/sipariş maili)'],
                    ['token' => '{baslangic_tarihi}', 'hint' => 'Abonelik başlangıcı'],
                    ['token' => '{bitis_tarihi}', 'hint' => 'Abonelik bitişi'],
                    ['token' => '{adet}', 'hint' => 'Abonelik adedi (abonelik/sipariş maili)'],
                    ['token' => '{fiyat}', 'hint' => 'Birim satış fiyatı'],
                ],
            ],
            [
                'group' => 'Teklif',
                'items' => [
                    ['token' => '{tur}', 'hint' => 'Belge başlığı: Birim Fiyat Teklifi veya Kesin Teklif'],
                    ['token' => '{teklif_no}', 'hint' => 'Teklif numarası'],
                    ['token' => '{teklif_turu}', 'hint' => 'Birim fiyat teklifi veya kesin teklif'],
                    ['token' => '{tarih}', 'hint' => 'Teklif tarihi'],
                    ['token' => '{gecerlilik}', 'hint' => 'Geçerlilik tarihi'],
                    ['token' => '{satici}', 'hint' => 'Satıcı. Birim fiyat teklifinde ünvan; kesin teklifte adres ve vergi satırları'],
                    ['token' => '{cari_unvani}', 'hint' => 'Cari ünvanı. Yalnızca resmi ad'],
                    ['token' => '{alici}', 'hint' => 'Alıcı. Kesin teklifte ünvan, vergi no ve e-posta'],
                    ['token' => '{kalemler}', 'hint' => 'Ürün satırları. Kesin teklifte müşteri belgesindeki tablo, ara toplam, KDV ve genel toplam. KDV ve ödeme uyarıları burada yazılmaz'],
                    ['token' => '{dinamik_kosullar}', 'hint' => 'Dinamik koşullar. Kesin teklifte kalemlerdeki taahhüt tipine göre KDV ve ödeme cümleleri'],
                    ['token' => '{not}', 'hint' => 'Müşteri notu. Koşulların üstünde durur'],
                    ['token' => '{kosullar}', 'hint' => 'Müşteri belgesinin altındaki sabit koşullar'],
                ],
            ],
            [
                'group' => 'Sipariş',
                'items' => [
                    ['token' => '{donem_baslangic}', 'hint' => 'Dönem başlangıcı'],
                    ['token' => '{donem_bitis}', 'hint' => 'Dönem bitişi'],
                ],
            ],
        ];
    }
}
