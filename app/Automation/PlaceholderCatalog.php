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
                'group' => 'Sipariş',
                'items' => [
                    ['token' => '{donem_baslangic}', 'hint' => 'Dönem başlangıcı'],
                    ['token' => '{donem_bitis}', 'hint' => 'Dönem bitişi'],
                ],
            ],
        ];
    }
}
