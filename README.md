# Property Operations API

A small REST API where plain-language maintenance requests are turned into
structured work orders by an AI model. Someone writes *"the elevator in the
lobby keeps stopping and makes a grinding noise"* — the API stores a work
order with a clean title, category, priority and summary.

**Stack:** PHP 8.4 · Laravel 13 · MySQL 8.4 · Redis 7 · nginx · Docker ·
Google Gemini

## Quick start (Docker)

Requirements: Docker with Compose, `make`.

```bash
cp .env.docker.example .env
# put your Gemini key in .env → GEMINI_API_KEY=...   (free key: https://aistudio.google.com)

make install        # builds images, starts containers, generates app key, migrates, seeds
```

The API is now at **http://localhost:8000** with 14 buildings and 6 work
orders seeded. Try it:

```bash
curl "http://localhost:8000/api/properties?city=Amsterdam"

curl -X POST http://localhost:8000/api/work-orders \
  -H "Content-Type: application/json" -H "Accept: application/json" \
  -d '{"property_id":"P-001","email":"tenant@gmail.com","description":"the elevator in the lobby keeps stopping and makes a grinding noise"}'
```

`make help` lists all commands (`make logs`, `make test`, `make migrate-fresh`, …).

### Without Docker

```bash
cp .env.example .env    # SQLite + file cache, no external services needed
composer install
touch database/database.sqlite
php artisan key:generate
php artisan migrate --seed
php artisan serve       # http://localhost:8000
```

## AI provider

**Google Gemini** (`gemini-2.5-flash` by default, configurable via
`GEMINI_MODEL`). Chosen because its free tier is more than enough here and
supports structured JSON output (`responseSchema`), so the model is
constrained to our exact categories and priorities at request time.

The call lives behind `App\Services\AI\Contracts\WorkOrderClassifierInterface`;
swapping providers means writing one class and rebinding one interface in
`AppServiceProvider`. The model's answer is never trusted blindly — see
[DECISION.md](DECISION.md).

## Endpoints

| Method | Path | Description |
|---|---|---|
| GET | `/api/properties` | List buildings, fullest first. Filters: `city`, `type`, `status`, `min_occupancy`, `per_page` |
| GET | `/api/properties/{id}` | One building + its open work order count |
| POST | `/api/work-orders` | **Uses AI.** Body: `property_id`, `email`, `description` → returns the classified work order |
| GET | `/api/work-orders` | List work orders, most urgent then newest. Filters: `property_id`, `status`, `priority`, `category`, `per_page` |

Responses that can go wrong do so with intent:

| Status | When |
|---|---|
| 200 with message | Filters matched nothing ("No properties matched the given filters.") |
| 404 | Unknown ID — `{"message": "Building P-999 was not found."}` |
| 422 | Validation: unknown building, implausible email, filter value outside the enums |
| 429 | Too many requests — ours (`throttle:10,1` on the AI endpoint) or Gemini's quota (with `Retry-After`) |
| 502 | The AI was unreachable or answered garbage twice — nothing was saved |

## How it's organised

```
routes/api.php                     thin route definitions
app/Http/Controllers/Api/          validate input, delegate, wrap in resources
app/Http/Requests|Resources/       validation rules / response shapes
app/Services/                      domain flows (WorkOrderService::create = classify → persist)
app/Services/AI/                   the AI seam: interface, Gemini client, validated DTO
app/Repositories/                  every Eloquent query, behind interfaces
app/Enums/                         single source of truth for types/statuses/categories/priorities
app/Models/                        Eloquent models + prefixed-ID generation (P-001, WO-1001)
database/seeders/                  14 curated buildings (with deliberate gaps) + demo work orders
```

Controller → Service → Repository, interfaces bound in `AppServiceProvider`.
The reasoning behind the non-obvious choices is in [DECISION.md](DECISION.md).