# İş Takip — ilk sürüm

Onaylanan kapsam: genel iş takibi altyapısı ve abonelik yenileme işleri.

## İş oluşturma

- Aktif aylık taahhütlü ve yıllık taahhütlü aboneliklerde, otomatik yenilemeden bağımsız olarak bitişe 15 gün kala iş açılır.
- Aylık taahhütsüz aboneliklerde yalnızca otomatik yenileme kapalıysa iş açılır.
- Kaçırılan günlük çalışmalarda kalan 0–15 günlük aralık yakalanır. Aynı abonelik ve bitiş tarihi için tek iş oluşur.
- Müşteri e-posta bildirim tercihi, iç iş oluşturulmasını engellemez.
- Kaynak ilişki geneldir (polymorphic). Aboneliğin dönem, ürün, adet, fiyat, para birimi ve yenileme koşulları anlık görüntü olarak saklanır.

## Kullanıcı akışı

1. Sorumlu ata / işi üstlen.
2. Güncel alış fiyatını doğrula; yeni satış fiyatını belirle. Katalog fiyatı yalnızca öneridir.
3. Mevcut teklif ekranından birim fiyat veya kesin teklif oluştur. İş ve dönem bağlantısı dönüşümlerde korunur.
4. Otomatik yenileme açıkken yeni dönem fiyat bildirimi; kapalıyken yenileme teklifi gönder.

Yenileme bağlamı yalnızca iç bağlantıda ve e-posta şablonlarında kullanılır. PDF ve müşteri teklif çıktısı normal tekliflerle aynıdır. Yenileme birim fiyat ve kesin teklifleri ayrı olaylara / şablonlara bağlıdır; normal teklif gönderim kuralları tetiklenmez. Şablonlarda `{yenileme_aciklamasi}`, `{abonelik_urun}`, `{abonelik_lisans_adedi}`, `{abonelik_bitis_tarihi}` ve `{abonelik_otomatik_yenileme}` alanları kullanılabilir.
5. Başarılı e-posta gönderimi veya açıklamalı harici bildirim kaydı sonrasında müşteri yanıtını / yenilemeyi takip et.
6. Onaylı tekliften “Abonelik işlemini kaydet” ile tedarikçide tamamlanan işlemi kaydet. Mevcut abonelikte fiyatlar/adet ve otomatik yenileme kaydedilir; bitiş tarihi değiştirilmez, zamanlanmış yenileme ilerletir. Yeni abonelikte ayrı sözleşme numarası kullanılır; eski abonelik yalnızca geçmiş bağlantısıdır. Tüm teklif kalemlerinin abonelik işlemleri kaydedilince iş otomatik tamamlanır. Yenilememe kararı gerekçeyle kapatılır.

Otomatik tarih uzaması tek başına işi kapatmaz. Notlar, atamalar, aşamalar ve fiyat kontrolü geçmişte tutulur. Liste; sorumlu, müşteri, aşama, tür ve takip tarihine göre filtrelenir. Dashboard acil işlere ulaşım sağlar.

## Toplu teklif ve kalem yanıtları

İş Takip tablosundan aynı müşterinin fiyat kontrolü tamamlanmış, devam eden teklifi olmayan işleri birlikte seçilebilir. Para birimi ve KDV oranı aynı olmalıdır. Her iş ayrı kalemdir; aynı ürünün farklı sözleşmeleri birleştirilmez. Teklif–iş bağlantısı çokludur; kaynak abonelik görüntüsü kalem üzerinde korunur.

Kesin teklifte her kalem için onay/ret ve yanıt notu kaydedilir. Toplu onay/ret yalnızca henüz yanıtı olmayan kalemlere uygulanır. Bir bölümü kabul edilmiş teklif “Kısmen onaylandı” görünür. Reddedilen iş yeniden fiyat/teklif hazırlamaya açılır. Abonelik işlemleri sadece kabul edilen kalemlerde yapılabilir ve ilgili iş bağımsız kapanır. Birim fiyat teklifinden kesin teklife geçerken alınmayan işlerin kilidi kaldırılır.

## Sonraki iş türleri

- Alış faturası girilmiş fakat satış faturası düzenlenmemiş sipariş: fatura düzenleme işi.
- Gönderim tarihinden itibaren belirlenen sürede yanıt alınmamış teklif: müşteri yanıt takibi işi.

Bu türlerin kuralları ilk sürümde çalışmaz; ortak model, kaynak bağlantısı, mükerrerlik anahtarı ve geçmiş altyapısı hazırdır.

## Çalıştırma

`php artisan migrate`

İlk tarama: `php artisan work-tasks:detect-renewals` veya İş Takip ekranındaki kontrol düğmesi.

Günlük tarama Europe/Istanbul saat diliminde 00:01'de, abonelik otomatik yenilemesi 00:10'da çalışır. Sunucuda Laravel scheduler çalışıyor olmalıdır. Tarama mevcut e-posta kurallarından bağımsızdır.

## Doğrulama

İş takibi, teklif yönetimi ve abonelik dönem takibi için 30 test / 480 doğrulama geçti. Blade şablonları ve Vite derlemesi başarılı. Yerel migration uygulandı ve ilk tarama çalıştırıldı.

Genel test çalıştırmasında 180 test geçti; mevcut AuthenticationTest, RegistrationTest ve ExampleTest içindeki üç beklenti başarısız. Giriş testi aktif hesap şartını, kayıt testi yönetici onayı akışını, ana sayfa testi girişe yönlendirmeyi dikkate almıyor. Bu testler ve mevcut kimlik doğrulama davranışı bu kapsamda değiştirilmedi.
