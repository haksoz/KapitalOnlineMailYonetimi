# Abonelik ve Tedarikçi Fatura Yönetim Sistemi — Şema ve Mimari

## Veritabanı şeması

### Tablolar ve ilişkiler

```
companies (Müşteriler)
    └── subscriptions (1:N)

service_providers (Microsoft, Hostinger vb.)
    ├── products (1:N)
    └── subscriptions (1:N)

suppliers (Tedarikçiler: Arena, Index vb.)
    ├── subscriptions (1:N)
    └── supplier_invoices (1:N)

products (Ürünler: Exchange Online Plan 1, M365 Business Standard vb.)
    └── subscriptions (1:N)

subscriptions (Sözleşme / Abonelik)
    ├── company, service_provider, product, supplier (N:1)
    └── invoice_items (1:N)

supplier_invoices (Tedarikçi faturaları)
    ├── supplier (N:1)
    └── invoice_items (1:N)

invoice_items (Fatura satırları — aylık maliyet + satış bilgisi)
    ├── supplier_invoice (N:1)
    └── subscription (N:1)
```

### Migration sırası

1. `create_companies_table`
2. `create_service_providers_table`
3. `create_suppliers_table`
4. `create_products_table` (service_provider_id FK)
5. `create_subscriptions_table` (company, service_provider, product, supplier FK)
6. `create_supplier_invoices_table` (supplier_id FK)
7. `create_invoice_items_table` (supplier_invoice_id, subscription_id FK)

### Index önerileri (migration’larda tanımlı)

- **companies:** `code`
- **subscriptions:** `sozlesme_no`, `durum`, `(company_id, durum)`, `(supplier_id, sozlesme_no)`
- **supplier_invoices:** `(supplier_id, fatura_no)` UNIQUE, `(donem_yil, donem_ay)`
- **invoice_items:** `subscription_id`, `supplier_invoice_id`, `(subscription_id, created_at)`, `satis_fatura_no`

Büyük listelerde filtreleme ve sayfalama bu index’ler üzerinden verimli çalışır.

---

## Model ilişkileri

| Model             | İlişkiler |
|-------------------|-----------|
| **Company**       | `subscriptions` HasMany |
| **ServiceProvider** | `products` HasMany, `subscriptions` HasMany |
| **Supplier**      | `subscriptions` HasMany, `supplierInvoices` HasMany |
| **Product**       | `serviceProvider` BelongsTo, `subscriptions` HasMany |
| **Subscription**  | `company`, `serviceProvider`, `product`, `supplier` BelongsTo; `invoiceItems` HasMany |
| **SupplierInvoice** | `supplier` BelongsTo; `invoiceItems` HasMany |
| **InvoiceItem**   | `supplierInvoice`, `subscription` BelongsTo |

---

## Klasör yapısı

```
app/
├── Contracts/           # Repository / servis arayüzleri
│   └── SubscriptionRepositoryInterface.php
├── Models/
│   ├── Company.php
│   ├── ServiceProvider.php
│   ├── Supplier.php
│   ├── Product.php
│   ├── Subscription.php
│   ├── SupplierInvoice.php
│   └── InvoiceItem.php
├── Repositories/        # Veri erişim katmanı
│   └── SubscriptionRepository.php
├── Services/            # İş mantığı katmanı
│   └── SubscriptionService.php
└── Providers/
    └── AppServiceProvider.php  # Contract → Repository binding
```

İleride eklenebilecekler: `CompanyRepository`, `SupplierInvoiceRepository`, `InvoiceItemRepository`, ilgili Service sınıfları ve Contract’lar.

---

## N+1 önleme

Liste çekerken ilişkileri `with()` ile yükleyin:

```php
// Örnek: Abonelik listesi
Subscription::query()
    ->with(['company:id,name,code', 'product:id,name,stock_code', 'supplier:id,name'])
    ->where('durum', Subscription::DURUM_ACTIVE)
    ->paginate(15);

// Fatura satırları listesi
InvoiceItem::query()
    ->with(['subscription.company', 'subscription.product', 'supplierInvoice'])
    ->whereHas('supplierInvoice', fn ($q) => $q->where('donem_yil', 2026))
    ->paginate(20);
```

Sadece ihtiyaç duyulan sütunları `id,name,code` şeklinde seçmek sorguyu hafifletir.

---

## Pagination

Büyük listeler için daima `paginate()` kullanın (cursor pagination da düşünülebilir):

```php
$perPage = request('per_page', 15);
Subscription::query()->with(...)->paginate($perPage);
```

Admin panelde `per_page` 15–50 arası sınırlanabilir.

---

## Sabitler (Subscription modeli)

- **Durum:** `active`, `cancelled`, `pending`
- **Taahhüt tipi:** `monthly_commitment`, `monthly_no_commitment`, `annual_commitment`
- **Faturalama periyodu:** `monthly`, `yearly`

Form ve filtrelerde bu sabitler kullanılabilir.

---

## MySQL kullanımı

`.env` örneği:

```
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=kapital_mail_yonetimi
DB_USERNAME=root
DB_PASSWORD=
```

Proje varsayılan olarak SQLite ile kurulur; production için MySQL’e geçmek için `.env` yukarıdaki gibi güncellenir ve `php artisan migrate` tekrar çalıştırılır.
