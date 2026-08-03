# Exchange Rates API (dinar-now)

Laravel 12 REST API that stores and serves currency exchange rates for the **dinar-now** frontend. Uses SQLite, caching, CORS, and rate limiting.

## Requirements

- PHP 8.2+
- Composer 2+
- SQLite (PHP `pdo_sqlite` enabled)

## Installation

```bash
cd exchange-rates-api
composer install
cp .env.example .env
php artisan key:generate
```

Create the SQLite database file if missing:

```bash
# Windows PowerShell
New-Item -ItemType File -Path database\database.sqlite -Force

# Linux / macOS
touch database/database.sqlite
```

```bash
php artisan migrate --seed
```

## Environment variables

| Variable | Description | Example |
|----------|-------------|---------|
| `APP_URL` | API public URL | `https://api.example.com` |
| `FRONTEND_URL` | Allowed CORS origin | `https://www.example.com` |
| `DB_CONNECTION` | Database driver | `sqlite` |
| `EXCHANGE_RATES_CACHE_TTL` | Cache TTL in seconds | `60` |

Do not commit real `.env` files.

## Local development

```bash
php artisan serve
# or if artisan serve fails on Windows:
php -S 127.0.0.1:8000 -t public
```

Test:

```bash
curl http://127.0.0.1:8000/api/v1/exchange-rates
```

Expected shape:

```json
{
  "success": true,
  "data": [
    {
      "currency": "USD",
      "name": "دولار أمريكي",
      "buy": 150000,
      "sell": 151000,
      "change": 0.25,
      "updated_at": "2026-07-31T15:30:00+00:00"
    }
  ]
}
```

## API

| Method | Path | Description |
|--------|------|-------------|
| GET | `/api/v1/exchange-rates` | Active currency rates (cached) |
| GET | `/api/v1/gold-rates` | Active gold rates (cached) |
| GET | `/api/v1/market-summary` | Market open/closed + USD spread |
| GET | `/up` | Health check |

Rate limit: 60 requests / minute / IP (`throttle:api`).

CORS allows only `FRONTEND_URL` (no `*` in production config).

## Caching

`ExchangeRateService` uses `Cache::remember` with key `exchange_rates.active` and TTL from `EXCHANGE_RATES_CACHE_TTL`.

## Shared hosting (document root = project root)

If the host cannot point to `public/`, the project root includes:

- `index.php` — front controller (uses `public/` as asset path)
- `.htaccess` — rewrites static files to `public/` and blocks sensitive folders

Upload the whole API folder, then open the domain root. Prefer pointing the vhost to `public/` when the host allows it.

## Live source (Qamar)

Default provider is **قمر الفجر** (`EXCHANGE_RATE_PROVIDER=qamar`):

```bash
php artisan rates:sync --force
```

Server cache / sync TTL: **120 seconds**. Scheduler: every 2 minutes.

## Admin dashboard

Branded admin panel (dinar-now logo / navy + gold):

- Web UI: `/admin/login`
- Demo user: `admin@dinar.local` / `password`
- Sanctum API auth (same pattern as CRM projects):
  - `POST /api/v1/auth/login`
  - `POST /api/v1/auth/logout`
  - `GET /api/v1/auth/me`

Seed admin:

```bash
php artisan db:seed --class=AdminUserSeeder --force
```

## Production deployment

1. Deploy PHP app to your server
2. Ensure `database/database.sqlite` is writable and outside public web root exposure
3. Set `APP_URL`, `FRONTEND_URL`, `APP_KEY`, `APP_DEBUG=false`
4. Run `php artisan migrate --force`
5. Point reverse proxy to `public/`

## Testing

```bash
php artisan test --filter=ExchangeRateTest
```

## Git workflow

This folder is its own Git repository. Do not initialize Git in the parent folder.

```bash
git add .
git commit -m "Your message"
```

## Future expansion (not implemented)

- `GET /api/v1/exchange-rates/{currency}`
- Rate history
- Admin dashboard
- Authentication
