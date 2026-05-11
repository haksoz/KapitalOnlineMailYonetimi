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
