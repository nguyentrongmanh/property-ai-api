# Property Operations API

A small REST API where plain-language maintenance requests are turned into
structured work orders by an AI model. Someone writes *"the elevator in the
lobby keeps stopping and makes a grinding noise"* — the API stores a work
order with a clean title, category, priority and summary.

**Stack:** PHP 8.4 · Laravel 13 · MySQL 8.4 · Redis 7 · nginx · Docker ·
Google Gemini via a shared AI service

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

The call lives behind `App\Services\AI\Contracts\AIServiceInterface`;
swapping providers means writing one class and rebinding one interface in
`AppServiceProvider`. The model's answer is never trusted blindly — see
[DECISION.md](DECISION.md).

## Endpoints

| Method | Path | Description |
|---|---|---|
| GET | `/api/properties` | List buildings, fullest first. Filters: `city`, `type`, `status`, `min_occupancy`, `per_page` |
| GET | `/api/properties/stats` | City stats: total properties and average occupancy per city |
| GET | `/api/properties/{id}` | One building + its open work order count |
| GET | `/api/properties/{id}/summary` | **Uses AI.** Short written summary of the building and its open work orders (cached 10 min) |
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
app/Services/AI/                   the AI seam: service, Gemini client, prompt builders, validator, DTOs
app/Repositories/                  every Eloquent query, behind interfaces
app/Enums/                         single source of truth for types/statuses/categories/priorities
app/Models/                        Eloquent models + prefixed-ID generation (P-001, WO-1001)
database/seeders/                  14 curated buildings (with deliberate gaps) + demo work orders
```

Controller → Service → Repository, interfaces bound in `AppServiceProvider`.
The reasoning behind the non-obvious choices is in [DECISION.md](DECISION.md).

## Postman

Import [postman_collection.json](postman_collection.json) — all six endpoints
with toggleable filter params, the AI creation request, and ready-made 404/422
failure cases. `base_url` defaults to `http://localhost:8000`.

## Tests

67 tests / 169 assertions, running on in-memory SQLite in well under a second:

```bash
make test               # inside Docker
php artisan test        # local
```

- **Feature**: all six endpoints — filtering, sorting, pagination,
  empty-state messages, 404/422/429/502 paths. The AI is swapped for a fake
  via its `AIServiceInterface` binding; tests assert the AI service is never
  called when validation fails and nothing is saved when it errors.
- **Integration**: both Eloquent repositories against a real schema
  (ordering, combined filters, prefixed-ID generation).
- **Unit**: the AI response validator (rejects 8 flavors of unusable model
  output) and both services with mocked repositories.
- Real HTTP is blocked in tests (`Http::preventStrayRequests`), so the suite
  can never hit the Gemini API.

## Left out / with more time

- **Queued classification**: for production I'd move the AI call to a queued
  job with a `pending` work order status, so slow model responses never block
  the HTTP request.
- **Race-proof IDs**: the prefixed-ID generation (`WO-1002`, …) isn't safe
  under heavy concurrent writes; I'd switch to ULIDs or a DB sequence.