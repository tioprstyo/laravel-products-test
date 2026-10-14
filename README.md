# Product Inventory (Laravel skills test)

A single page with a Bootstrap form (Product name, Quantity in stock, Price per item).
Submissions are sent with Ajax and stored in a JSON file, and the table below the form
refreshes without a page reload.

- Rows are ordered by the datetime they were submitted.
- Columns: Product name, Quantity in stock, Price per item, Datetime submitted, Total value number.
- Total value number = Quantity in stock × Price per item. The last row shows the sum of all totals.
- Extra credit: every row can be edited inline (Edit → Save / Cancel, Enter / Escape keys).

## Requirements

PHP 8.3+ and Composer. No database is needed.

## Run it

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan serve
```

Open http://127.0.0.1:8000.

Optional sample data: `php artisan db:seed` (writes three products into the JSON file).

From the zip: the archive already contains `vendor/` and a configured `.env`, so extract it and
either run `php artisan serve` or point Apache at the folder. A root `.htaccess` forwards requests
to `public/`. `storage/` must be writable by the web server.

## Where the data lives

`storage/app/products.json` (configurable with `PRODUCTS_FILE` in `.env`):

```json
{
    "products": [
        {
            "id": "1b0f…",
            "product_name": "Wireless Mouse",
            "quantity": 25,
            "price": 19.99,
            "submitted_at": "2026-10-15T01:00:00.000+07:00",
            "updated_at": "2026-10-15T01:00:00.000+07:00"
        }
    ]
}
```

Writes take an exclusive file lock so simultaneous submissions are not lost.

## Code map

| Part | File |
|---|---|
| Routes | `routes/web.php` |
| Controller | `app/Http/Controllers/ProductController.php` |
| Validation | `app/Http/Requests/ProductRequest.php` |
| JSON storage | `app/Services/ProductStore.php`, `config/products.php` |
| Seeder | `database/seeders/ProductSeeder.php` |
| Page | `resources/views/products/index.blade.php` |
| Ajax / editing | `public/js/products.js` |
| Tests | `tests/Feature/ProductTest.php` (`php artisan test`) |

### Endpoints

| Method | URL | Purpose |
|---|---|---|
| GET | `/` | Page |
| GET | `/products` | Rows + grand total (JSON) |
| POST | `/products` | Create |
| PUT | `/products/{id}` | Edit |

The default Laravel migrations are untouched; the app does not use a database because the task
requires file storage (sessions and cache use the file driver).
