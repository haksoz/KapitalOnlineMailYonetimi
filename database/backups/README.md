# Veritabanı yedekleri (MySQL/MariaDB)

Bu klasör, projenin MySQL/MariaDB veritabanının yedeklerini tutar.

## Yedek almak

```bash
php artisan db:backup
```

Yedek `database/backups/backup_YYYY-MM-DD_HH-MM-SS.sql` adıyla oluşturulur. Farklı bir dizin için:

```bash
php artisan db:backup --path=/yol/istediginiz/klasor
```

## Geri yüklemek

```bash
mysql -h HOST -P PORT -u USER -p VERITABANI_ADI < database/backups/backup_2026-03-14_14-30-00.sql
```

`.env` dosyanızdaki `DB_HOST`, `DB_PORT`, `DB_USERNAME`, `DB_PASSWORD`, `DB_DATABASE` değerlerini kullanın.

**Not:** `*.sql` yedek dosyaları hassas veri içerebileceği için proje `.gitignore` içinde yoksa ekleyebilirsiniz; böylece yedekler Git’e commit edilmez.
