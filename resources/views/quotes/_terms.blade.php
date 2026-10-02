<div class="mt-6 border-t border-gray-200 pt-4 text-sm text-gray-700 space-y-3">
    @if (filled($quote->notes))
        <p class="whitespace-pre-line">{{ $quote->notes }}</p>
    @endif
    <ol class="space-y-1">
        <li>1. Sipariş geçildikten sonra iade veya iptal hakkı yoktur.</li>
        <li>2. Sipariş geçilirken bir sonraki sene otomatik yenileme yapılıp yapılmayacağının iletilmesi rica edilir. Otomatik yenileme fiyatı sabitlemez. Fiyatlarda değişim var ise bilgi verilir.</li>
        <li>3. Taahhütlü siparişlerde 12 ay içerisinde adet azaltma hakkı bulunmamaktadır.</li>
        <li>4. Taahhütlü siparişlerde 12 ay içerisinde istenilen zaman adet arttırma yapılabilir, kalan gün sayısı üzerinden fatura kesilir.</li>
        <li>5. Aylık taahhütsüz geçilen siparişlerde fiyat koruması yoktur, üyelik devam ettiği sürece aylık olarak her ayın güncel fiyatları ile faturalandırılır. Yıllık Taahütlü siparişerin 12 aylık toplam bedeli sipariş geçildiğinde faturalandırılır.</li>
        <li>6. Sizlerden olumlu olumsuz bilgi gelmemesi durumunda, otomatik yenileme yapılmayacak ve yenilemeler sistem tarafından otomatik durdurulacaktır.</li>
    </ol>
</div>
