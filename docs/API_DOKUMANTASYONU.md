# Mail Yönetimi API Dokümantasyonu

## Genel Bilgiler

- **Base URL**: `http://kapitalonlinemailyonetimi.test/api`
- **Authentication**: Bearer Token (Laravel Sanctum)
- **Content-Type**: `application/json`

## Authentication

Tüm isteklerde Authorization header ile API token gönderilmelidir:

```http
Authorization: Bearer YOUR_API_TOKEN
```

### API Token Oluşturma

API token oluşturmak için Laravel artisan komutunu kullanabilirsiniz:

```bash
php artisan tinker
```

```php
$user = App\Models\User::first();
$token = $user->createToken('api-token', ['*'], now()->addYear())->plainTextToken;
echo $token;
```

## Endpoint'ler

### 1. Cari Bazlı Endpoint'ler

#### 1.1 Cari Arama (Vergi Numarası ile)

**Endpoint**: `GET /api/caris/search`

**Query Parameters**:
- `tax_number` (required): Vergi numarası
- `country_code` (optional): Ülke kodu (default: TR)

**Response**:
```json
{
  "success": true,
  "data": {
    "id": 1,
    "uuid": "uuid-here",
    "name": "Firma Adı",
    "short_name": "Kısa Ad",
    "email": "email@example.com",
    "tax_number": "1234567890",
    "country_code": "TR",
    "cari_type": "customer",
    "created_at": "2024-01-01 00:00:00",
    "updated_at": "2024-01-01 00:00:00"
  }
}
```

**Örnek İstek**:
```bash
curl -X GET "http://kapitalonlinemailyonetimi.test/api/caris/search?tax_number=1234567890" \
  -H "Authorization: Bearer YOUR_API_TOKEN"
```

#### 1.2 Cari'nin Abonelikleri

**Endpoint**: `GET /api/caris/{cari_id}/subscriptions`

**Response**:
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "sozlesme_no": "S123",
      "baslangic_tarihi": "2024-01-01",
      "bitis_tarihi": "2025-01-01",
      "durum": "active",
      "auto_renew": true,
      "quantity": 5,
      "usd_birim_satis": "8.0000",
      "customer_cari": { ... },
      "provider_cari": { ... },
      "service_provider": { ... },
      "product": { ... }
    }
  ]
}
```

#### 1.3 Yaklaşan Abonelik Yenilemeleri

**Endpoint**: `GET /api/caris/{cari_id}/upcoming-renewals`

**Açıklama**: Sonraki 30 gün içinde bitecek ve auto_renew=true olan aktif abonelikleri döndürür.

**Response**:
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "bitis_tarihi": "2024-05-20",
      "usd_birim_satis": "8.0000",
      ...
    }
  ]
}
```

#### 1.4 Cari'nin Pending Billings'leri

**Endpoint**: `GET /api/caris/{cari_id}/pending-billings`

**Query Parameters**:
- `status` (optional): pending, invoiced, cancelled, postponed

**Response**:
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "period_start": "2024-05-01",
      "period_end": "2024-05-31",
      "status": "pending",
      "expected_satis_tl": 400.00,
      "actual_satis_tl": 420.00,
      "subscription": { ... }
    }
  ]
}
```

#### 1.5 Cari'nin Sales Invoices'ları

**Endpoint**: `GET /api/caris/{cari_id}/sales-invoices`

**Açıklama**: Kesinleşen borçları (faturaları) döndürür.

**Response**:
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "our_invoice_number": "FTN000001",
      "our_invoice_date": "2024-05-01",
      "total_amount_tl": 480.00,
      "customer_cari": { ... },
      "lines": [ ... ]
    }
  ]
}
```

#### 1.6 Cari'nin Ürün Talepleri

**Endpoint**: `GET /api/caris/{cari_id}/product-requests`

**Query Parameters**:
- `status` (optional): pending, approved, rejected, completed

**Response**:
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "product_name": "Yeni Ürün",
      "description": "Ürün açıklaması",
      "status": "pending",
      "cari": { ... },
      "product": { ... }
    }
  ]
}
```

### 2. Subscription Endpoint'leri

#### 2.1 Tüm Abonelikler

**Endpoint**: `GET /api/subscriptions`

**Query Parameters**:
- `customer_cari_id` (optional): Müşteri cari ID
- `durum` (optional): active, cancelled, pending

**Response**:
```json
{
  "success": true,
  "data": [ ... ],
  "meta": {
    "current_page": 1,
    "last_page": 5,
    "per_page": 50,
    "total": 250
  }
}
```

#### 2.2 Tekil Abonelik Detayı

**Endpoint**: `GET /api/subscriptions/{id}`

**Response**:
```json
{
  "success": true,
  "data": {
    "id": 1,
    "sozlesme_no": "S123",
    "baslangic_tarihi": "2024-01-01",
    "bitis_tarihi": "2025-01-01",
    "usd_birim_satis": "8.0000",
    ...
  }
}
```

### 3. PendingBilling Endpoint'leri

#### 3.1 Tüm Pending Billings

**Endpoint**: `GET /api/pending-billings`

**Query Parameters**:
- `subscription_id` (optional): Abonelik ID
- `status` (optional): pending, invoiced, cancelled, postponed

**Response**:
```json
{
  "success": true,
  "data": [ ... ],
  "meta": { ... }
}
```

**Önemli Alanlar**:
- `expected_satis_tl`: Beklenen satış tutarı
- `actual_satis_tl`: Kesinleşen satış tutarı
- `fee_difference_tl`: Fark tutarı

#### 3.2 Tekil Pending Billing Detayı

**Endpoint**: `GET /api/pending-billings/{id}`

### 4. SalesInvoice Endpoint'leri

#### 4.1 Tüm Satış Faturaları

**Endpoint**: `GET /api/sales-invoices`

**Query Parameters**:
- `customer_cari_id` (optional): Müşteri cari ID

**Response**:
```json
{
  "success": true,
  "data": [ ... ],
  "meta": { ... }
}
```

#### 4.2 Tekil Satış Faturası Detayı

**Endpoint**: `GET /api/sales-invoices/{id}`

### 5. ProductRequest Endpoint'leri

#### 5.1 Tüm Ürün Talepleri

**Endpoint**: `GET /api/product-requests`

**Query Parameters**:
- `cari_id` (optional): Cari ID
- `status` (optional): pending, approved, rejected, completed

**Response**:
```json
{
  "success": true,
  "data": [ ... ],
  "meta": { ... }
}
```

#### 5.2 Ürün Talebi Oluşturma

**Endpoint**: `POST /api/product-requests`

**Request Body**:
```json
{
  "cari_id": 1,
  "product_id": 5,
  "product_name": "Yeni Ürün",
  "description": "Ürün açıklaması",
  "notes": "Notlar"
}
```

**Not**: `product_id` veya `product_name` alanlarından en az biri zorunludur.

**Response**:
```json
{
  "success": true,
  "data": {
    "id": 1,
    "cari_id": 1,
    "product_id": 5,
    "product_name": "Yeni Ürün",
    "status": "pending",
    ...
  }
}
```

#### 5.3 Tekil Ürün Talebi Detayı

**Endpoint**: `GET /api/product-requests/{id}`

## Hata Yanıtları

**401 Unauthorized**:
```json
{
  "success": false,
  "message": "Unauthenticated"
}
```

**404 Not Found**:
```json
{
  "success": false,
  "message": "Cari not found"
}
```

**422 Validation Error**:
```json
{
  "message": "The tax_number field is required.",
  "errors": {
    "tax_number": ["The tax_number field is required."]
  }
}
```

## Örnek Kullanım Senaryoları

### Senaryo 1: Vergi Numarası ile Müşteri Bulma ve Aboneliklerini Listeleme

```bash
# 1. Cari bul
curl -X GET "http://kapitalonlinemailyonetimi.test/api/caris/search?tax_number=1234567890" \
  -H "Authorization: Bearer YOUR_API_TOKEN"

# 2. Abonelikleri getir (cari_id: 1)
curl -X GET "http://kapitalonlinemailyonetimi.test/api/caris/1/subscriptions" \
  -H "Authorization: Bearer YOUR_API_TOKEN"

# 3. Yaklaşan yenilemeleri getir
curl -X GET "http://kapitalonlinemailyonetimi.test/api/caris/1/upcoming-renewals" \
  -H "Authorization: Bearer YOUR_API_TOKEN"
```

### Senaryo 2: Beklenen ve Kesinleşen Satış Tutarlarını Görüntüleme

```bash
# Pending billings (beklenen satış)
curl -X GET "http://kapitalonlinemailyonetimi.test/api/caris/1/pending-billings" \
  -H "Authorization: Bearer YOUR_API_TOKEN"

# Sales invoices (kesinleşen borç)
curl -X GET "http://kapitalonlinemailyonetimi.test/api/caris/1/sales-invoices" \
  -H "Authorization: Bearer YOUR_API_TOKEN"
```

### Senaryo 3: USD Fiyatlarını Görüntüleme

USD satış fiyatı abonelik detaylarında `usd_birim_satis` alanında bulunur.

```bash
curl -X GET "http://kapitalonlinemailyonetimi.test/api/caris/1/subscriptions" \
  -H "Authorization: Bearer YOUR_API_TOKEN"
```

### Senaryo 4: Ürün Talebi Oluşturma

```bash
curl -X POST "http://kapitalonlinemailyonetimi.test/api/product-requests" \
  -H "Authorization: Bearer YOUR_API_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "cari_id": 1,
    "product_name": "Yeni Microsoft 365 Lisansı",
    "description": "Business Standard planı, 10 kullanıcı",
    "notes": "Acil ihtiyaç"
  }'
```

## Environment Variables

`.env` dosyasına şu ayarları ekleyin:

```env
API_TOKEN_EXPIRATION_MINUTES=525600
```

Bu ayar API token'ların geçerlilik süresini dakika cinsinden belirler (varsayılan: 1 yıl).

---

## Master Platform Endpoint'leri

Master platform için özel olarak tasarlanmış endpoint'ler. Pull Model kullanarak veri çekimi yapar.

### 6.1 Ürün Listesi (Satış Kanalı için)

**Endpoint**: `GET /api/master/products`

**Açıklama**: Satış kanalı için ürün listesi ve güncel fiyatlar. Sadece satış USD fiyatları içerir.

**Query Parameters**:
- `service_provider_id` (optional): Servis sağlayıcı ID'si
- `search` (optional): Ürün adı, stok kodu veya açıklama araması

**Response**:
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "name": "Microsoft 365 Business Standard",
      "stock_code": "M365-BIZ-STD",
      "description": "Business Standard planı",
      "satis_usd_monthly_commitment": 12.00,
      "satis_usd_monthly_no_commitment": 15.00,
      "satis_usd_yearly_commitment": 120.00,
      "service_provider": {
        "id": 1,
        "name": "Microsoft",
        "code": "MSFT"
      },
      "created_at": "2024-01-01 00:00:00",
      "updated_at": "2024-01-01 00:00:00"
    }
  ],
  "meta": {
    "current_page": 1,
    "last_page": 5,
    "per_page": 50,
    "total": 250
  }
}
```

**Örnek İstek**:
```bash
curl -X GET "http://kapitalonlinemailyonetimi.test/api/master/products?search=Microsoft" \
  -H "Authorization: Bearer YOUR_API_TOKEN"
```

### 6.2 Kullanıcı Bazlı Abonelikler

**Endpoint**: `GET /api/master/subscriptions`

**Açıklama**: Belirli bir kullanıcının aboneliklerini listeler. Master platform müşteri takibi için kullanır.

**Query Parameters**:
- `user_id` (required): Müşteri cari ID'si
- `status` (optional): active, cancelled, pending, expired
- `active_only` (optional): true/false - Sadece aktif abonelikler

**Response**:
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "sozlesme_no": "S123456",
      "baslangic_tarihi": "2024-01-01",
      "bitis_tarihi": "2025-01-01",
      "durum": "active",
      "auto_renew": true,
      "quantity": 10,
      "usd_birim_satis": "12.0000",
      "customer_cari": {
        "id": 123,
        "name": "Test Firma",
        "email": "test@firma.com"
      },
      "product": {
        "id": 1,
        "name": "Microsoft 365 Business Standard"
      },
      "service_provider": {
        "id": 1,
        "name": "Microsoft"
      }
    }
  ],
  "meta": {
    "current_page": 1,
    "last_page": 3,
    "per_page": 50,
    "total": 125
  }
}
```

**Örnek İstek**:
```bash
curl -X GET "http://kapitalonlinemailyonetimi.test/api/master/subscriptions?user_id=123&status=active" \
  -H "Authorization: Bearer YOUR_API_TOKEN"
```

### 6.3 Kullanıcı Bazlı Siparişler

**Endpoint**: `GET /api/master/orders`

**Açıklama**: Müşterinin açık siparişlerini (pending) ve faturalanmış siparişlerini (invoiced) bir arada listeler. Borçlandırma için kullanılır.

**Query Parameters**:
- `user_id` (required): Müşteri cari ID'si
- `status` (optional): pending, invoiced, all (default: all)
- `date_from` (optional): Başlangıç tarihi (YYYY-MM-DD)
- `date_to` (optional): Bitiş tarihi (YYYY-MM-DD)
- `page` (optional): Sayfa numarası (default: 1)

**Response**:
```json
{
  "success": true,
  "data": [
    {
      "id": 456,
      "type": "pending",
      "order_number": "PB-000456",
      "period_start": "2024-05-01",
      "period_end": "2024-05-31",
      "status": "pending",
      "amount_tl": 480.00,
      "amount_usd": 12.00,
      "currency": "TL",
      "due_date": "2024-05-31",
      "description": "Microsoft 365 Business Standard",
      "subscription_id": 123,
      "customer": {
        "id": 123,
        "name": "Test Firma"
      },
      "product": {
        "id": 1,
        "name": "Microsoft 365 Business Standard"
      },
      "service_provider": {
        "id": 1,
        "name": "Microsoft"
      },
      "created_at": "2024-05-01 10:00:00",
      "updated_at": "2024-05-01 10:00:00"
    },
    {
      "id": 789,
      "type": "invoiced",
      "order_number": "FTN000001",
      "period_start": null,
      "period_end": null,
      "status": "invoiced",
      "amount_tl": 500.00,
      "amount_usd": null,
      "currency": "TL",
      "due_date": "2024-04-01",
      "description": "Satış Faturası",
      "subscription_id": null,
      "customer": {
        "id": 123,
        "name": "Test Firma"
      },
      "product": null,
      "service_provider": null,
      "lines": [
        {
          "description": "Microsoft 365 Lisans",
          "quantity": 10,
          "unit_price_tl": 50.00,
          "total_tl": 500.00
        }
      ],
      "created_at": "2024-04-01 14:30:00",
      "updated_at": "2024-04-01 14:30:00"
    }
  ],
  "meta": {
    "current_page": 1,
    "last_page": 2,
    "per_page": 50,
    "total": 75
  }
}
```

**Örnek İstek**:
```bash
curl -X GET "http://kapitalonlinemailyonetimi.test/api/master/orders?user_id=123&status=pending&date_from=2024-05-01" \
  -H "Authorization: Bearer YOUR_API_TOKEN"
```

### 6.4 Abonelik Durum Özeti

**Endpoint**: `GET /api/master/subscription-summary`

**Açıklama**: Müşterinin abonelik ve sipariş durumlarının hızlı özeti. Dashboard için ideal.

**Query Parameters**:
- `user_id` (required): Müşteri cari ID'si

**Response**:
```json
{
  "success": true,
  "data": {
    "subscriptions": {
      "active": 5,
      "expired": 2,
      "auto_renew_disabled": 1,
      "upcoming_renewals": 3
    },
    "orders": {
      "pending": 8,
      "invoiced": 45
    }
  }
}
```

**Örnek İstek**:
```bash
curl -X GET "http://kapitalonlinemailyonetimi.test/api/master/subscription-summary?user_id=123" \
  -H "Authorization: Bearer YOUR_API_TOKEN"
```

---

## Master Platform Kullanım Senaryoları

### Senaryo 1: Satış Panelinde Ürün Listeleme

Master platform satış panelinde mail lisansı almak isteyen kullanıcıya ürünleri göstermek için:

```bash
# 1. Tüm ürünleri çek
curl -X GET "http://kapitalonlinemailyonetimi.test/api/master/products" \
  -H "Authorization: Bearer YOUR_API_TOKEN"

# 2. Belirli servisi sağlayıcıya göre filtrele
curl -X GET "http://kapitalonlinemailyonetimi.test/api/master/products?service_provider_id=1" \
  -H "Authorization: Bearer YOUR_API_TOKEN"
```

### Senaryo 2: Müşteri Abonelik Takibi

Master platform müşterinin abonelik durumunu göstermek için:

```bash
# 1. Müşterinin aktif abonelikleri
curl -X GET "http://kapitalonlinemailyonetimi.test/api/master/subscriptions?user_id=123&status=active" \
  -H "Authorization: Bearer YOUR_API_TOKEN"

# 2. Yaklaşan yenilemeler (30 gün içinde)
curl -X GET "http://kapitalonlinemailyonetimi.test/api/master/subscriptions?user_id=123&active_only=true" \
  -H "Authorization: Bearer YOUR_API_TOKEN"

# 3. Hızlı durum özeti
curl -X GET "http://kapitalonlinemailyonetimi.test/api/master/subscription-summary?user_id=123" \
  -H "Authorization: Bearer YOUR_API_TOKEN"
```

### Senaryo 3: Müşteri Borçlandırma

Master platform müşterinin açık ve faturalanmış siparişlerini çekmek için:

```bash
# 1. Tüm siparişler
curl -X GET "http://kapitalonlinemailyonetimi.test/api/master/orders?user_id=123" \
  -H "Authorization: Bearer YOUR_API_TOKEN"

# 2. Sadece açık siparişler (bekleyen ödemeler)
curl -X GET "http://kapitalonlinemailyonetimi.test/api/master/orders?user_id=123&status=pending" \
  -H "Authorization: Bearer YOUR_API_TOKEN"

# 3. Belirli dönemdeki siparişler
curl -X GET "http://kapitalonlinemailyonetimi.test/api/master/orders?user_id=123&date_from=2024-05-01&date_to=2024-05-31" \
  -H "Authorization: Bearer YOUR_API_TOKEN"
```

---

## Master Platform Notları

### Authentication
- Tüm Master endpoint'leri için **Bearer Token** gereklidir
- Token oluşturma: `php artisan tinker` → `$user->createToken('master-api', ['*'], now()->addYear())->plainTextToken`

### Rate Limiting
- Varsayılan: 60 istek/dakika
- Master platform için özel rate limiting yapılandırılabilir

### Pagination
- Tüm listeleme endpoint'leri **50 kayıt/şeklinde** paginate edilir
- `page` parametresi ile sayfalama yapılabilir
- Response'ta `meta` objesi sayfa bilgilerini içerir

### Error Handling
- **401**: Token geçersiz/expired
- **404**: Kaynak bulunamadı
- **422**: Validation hatası (örn: user_id geçersiz)
- **500**: Sunucu hatası

### Veri Güncelliği
- **Pull Model** kullanılır - Master platform istediği zaman veriyi çeker
- **Cache önerisi**: Ürün listesi için 1 saat, abonelik/sipariş için 5 dakika cache
- **Webhook**: İleride gerçek zamanlı güncelleme için webhook sistemi eklenebilir
