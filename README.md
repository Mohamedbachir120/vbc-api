# Virtual Business Card API — Slim 4 + Eloquent (MySQL)

PHP/Slim implementation of the `virtual-business-card` API spec, using
**Eloquent** (`illuminate/database`) as the ORM instead of raw SQL/PDO, and
**MySQL** instead of the original Postgres/Prisma setup.

## Stack

- **Slim Framework 4** — routing, middleware, PSR-7
- **illuminate/database** (Eloquent) — ORM, ActiveRecord-style models
- **firebase/php-jwt** — JWT issuing/verification
- **endroid/qr-code** — QR PNG generation (base64 data URI)
- **vlucas/phpdotenv** — `.env` loading
- **ramsey/uuid** — UUID v4 generation for cards (mirrors Prisma's `@default(uuid())`)

## Setup

```bash
composer install
cp .env.example .env
# edit .env with your MySQL credentials + a real JWT_SECRET

mysql -u root -p < database/schema.sql

php -S localhost:3000 -t public
```

The schema in `database/schema.sql` is a direct MySQL translation of your
`prisma/schema.prisma` (same tables/columns, snake_case DB columns, same
indexes and foreign keys with `ON DELETE CASCADE`).

## Project layout

```
public/index.php       Slim bootstrap, middleware, global error handler
routes/api.php         All route definitions
src/Database.php       Eloquent Capsule (connection) bootstrap
src/Models/            User, Card, CardView (Eloquent models)
src/Controllers/       AuthController, CardController, StatsController, PublicController
src/Middleware/        AuthMiddleware (Bearer JWT guard)
src/Support/           JwtHelper, ApiResponse, ApiException, Validator, VCardBuilder, QrCodeHelper
database/schema.sql    MySQL schema (users, cards, card_views)
```

## Design notes

- **camelCase <-> snake_case**: the API spec uses `accentLight`, `isActive`,
  `userId`, etc. The `Card` and `CardView` models expose Eloquent
  accessors/mutators (`getAccentLightAttribute` / `setAccentLightAttribute`,
  etc.) so controllers can read/write camelCase while the DB stays
  snake_case — no manual array-key juggling in the controllers.
- **UUIDs**: generated in a `Card::creating` model event (`ramsey/uuid`),
  equivalent to Prisma's `@default(uuid())`.
- **Errors**: every failure path throws an `App\Support\ApiException`
  (`ApiException::notFound()`, `::unauthorized()`, `::conflict()`,
  `::validation()`, etc.). A single error handler in `public/index.php`
  catches these and renders the exact `{ success, code, message, details? }`
  shape from your spec — no per-controller try/catch duplication.
- **Ownership checks**: `CardController::findOwnedCard()` returns 404 if the
  card doesn't exist at all, and 403 if it exists but belongs to another
  user — applied consistently across show/update/delete/toggle/qr/vcard. The
  spec only lists 404 for `GET /api/cards/:id`, but returning 403 for a
  card you don't own is the safer choice; tell me if you'd rather it 404
  on cross-user access too (security-through-obscurity vs. explicit 403).
- **`viewsLast30Days`**: always exactly 30 zero-filled entries, computed
  with one grouped query (`DATE(viewed_at)`) plus a PHP fill loop — matches
  the `frontend_tips.chart_data` guarantee.
- **vCard / QR routes**: return raw bytes / a `data:` URI exactly as
  documented, never wrapped in `fetch().json()`-style payloads for the
  `.vcf` routes.
- **`/health` uptime**: this is per-request PHP execution time
  (`microtime(true) - REQUEST_TIME_FLOAT`), not real server process uptime —
  PHP's request-per-process model (unlike Node's long-lived process) has no
  native equivalent. If you need real uptime, track a "server started at"
  timestamp in a cache/file and diff against that instead.

## Testing note

This sandbox's network allowlist doesn't include Packagist/getcomposer.org,
so I couldn't run a live `composer install` + integration boot here. Every
file passed `php -l` (syntax-checked, zero errors), and I hand-traced the
Eloquent attribute/mutator wiring and route table against your spec. Run
`composer install` locally and it should boot straight away — let me know
if anything errors and I'll fix it on the spot.
