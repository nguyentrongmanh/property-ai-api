# Design Decisions

A short log of the non-obvious choices made in this project and why.

## Data layer

### Prefixed string primary keys (`P-001`, `WO-1001`)
The take-home spec shows human-readable IDs in its example payloads. Rather than
exposing auto-increment integers and mapping them, the models use string primary
keys that match the spec exactly. A small shared trait
(`App\Models\Concerns\HasPrefixedId`) generates the next ID on `creating`, so
`POST /api/work-orders` produces `WO-1001`, `WO-1002`, … automatically.

Trade-off: the "next number" lookup is not race-proof under heavy concurrent
writes. For this scale it is fine; in production I would move to ULIDs or a
database sequence.

### `property_id` column on `work_orders`
The spec's work-order JSON uses `property_id`, while the entity itself is a
"building". The column is named `property_id` (pointing at `buildings.id`) so the
API payload maps 1:1 to the database without renaming in resources.

### Backed PHP enums for type/status/category/priority
`BuildingType`, `BuildingStatus`, `WorkOrderCategory`, `WorkOrderPriority` and
`WorkOrderStatus` are string-backed enums, cast on the models. This gives one
source of truth for validation (`Rule::enum`), for constraining what the AI is
allowed to answer, and for API filter validation. `WorkOrderPriority::weight()`
centralises the urgency ordering used to sort work orders.

### Curated seeders instead of factories
The spec asks for at least twelve buildings with deliberate variety and
deliberately missing fields, and for seed data kept in the repo as a
deliverable. Hand-written seed arrays make the gaps intentional (e.g. `P-008`
has no occupancy rate, `P-010` no city, `P-011` no type), keep every reviewer's
database identical, and let the README reference real IDs. `updateOrCreate`
keyed on ID makes re-seeding idempotent.

Factories still exist — they are for the test suite, with states such as
`incomplete()`, `urgent()` and `completed()` for edge cases.

### Nullable columns over defaults
`type`, `city`, `units`, `occupancy_rate` and `amenities` are nullable rather
than defaulted, because the spec explicitly requires the code to cope with
incomplete data. Only `status` fields get defaults (`active` / `open`), mirrored
in the models' `$attributes`.

## AI integration

### Google Gemini as the provider
Free tier generous enough for reviewers to run the project without paying,
solid structured-output support (`responseSchema`), and a simple key signup.
The call sits behind a `WorkOrderClassifier` interface so the provider can be
swapped without touching controllers or services.

## Infrastructure

### Docker: nginx + PHP-FPM + MySQL 8.4 + Redis 7
Four containers on one bridge network. nginx serves `public/` and proxies PHP
to the FPM container; MySQL has a healthcheck so the app container waits for it;
named volumes persist MySQL and Redis data.

### Debian-based `php:8.4-fpm` image
Alpine was tried first for its smaller CVE surface, but PECL's flaky DNS made
`pecl install redis` unreliable during builds. The standard Debian image with
`pecl channel-update` proved more dependable. PHP 8.4 matches the project
guidelines (composer.json allows `^8.3`).

### Two env examples
- `.env.example` — local, dependency-free profile: SQLite, file cache/session,
  sync queue.
- `.env.docker.example` — Docker profile: MySQL (`mysql` host), Redis cache,
  session and queue (`redis` host), compose port variables.

### Makefile as the task runner
Wraps the docker compose workflow (`make up`, `make migrate`, `make test`, …)
so the "running in under ten minutes" requirement holds without memorising
compose commands. npm targets were removed since this is an API-only server.
