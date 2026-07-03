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

## Architecture

### Three layers: Controller → Service → Repository, each with a base
Controllers stay thin (validate → delegate → wrap in a resource), services own
domain flows, repositories own every Eloquent query. Each layer has a base
abstraction so concrete classes only add what is domain-specific:

- `RepositoryInterface` / `CrudServiceInterface` — generic CRUD contracts
  (`filter`, `detail`, `create`, `edit`, `delete`).
- `BaseCrudService` — default pass-through to a repository, including
  `per_page` resolution.
- `BaseApiController` — takes a `CrudServiceInterface`, exposes the response
  helpers (`respondList`, `respondItem`, `respondCreated`, `respondEmptyList`,
  `respondError`) and applies the child's configured `$resource` class-string.

Interfaces are bound to implementations in `AppServiceProvider`.

### Centralised exception rendering
All API error rendering lives in `bootstrap/app.php` (`shouldRenderJsonWhen`
for `api/*`, model-aware 404 messages, AI failure mapping) rather than
`render()` methods on exception classes — one place to see the whole error
surface. HTTP status codes use `Symfony\Component\HttpFoundation\Response`
constants, never magic numbers.

Unhandled API exceptions return a generic message and also include
`status_code` in the JSON body so clients can branch on a stable field without
parsing free-form messages.

### Request traceability with correlation IDs
Global middleware ensures every request has `x-correlation-id`:

- inbound value is preserved when provided
- missing value is generated server-side and returned on response headers

Another global middleware logs request lifecycle events before and after
controller execution (`http.request.started`, `http.request.finished`) and
includes the same `correlation_id` in both entries. Internal API failures log
`http.request.failed` with that same ID, so a single request can be followed
across normal and error paths.

### Pagination with explicit empty-state messages
List endpoints return Laravel's standard paginated shape (`data` + `links` +
`meta`, `per_page` capped at 100, filter params carried into page links via
`withQueryString()`). When nothing matches, the API returns a 200 with an
explicit message instead of a bare empty list, per the spec's "say so clearly"
requirement.

## AI integration

### Google Gemini as the provider
Free tier generous enough for reviewers to run the project without paying,
solid structured-output support (`responseSchema`), and a simple key signup.
The call sits behind `App\Services\AI\Contracts\AIServiceInterface`, so the
provider can be swapped by rebinding one interface in `AppServiceProvider`.

### Trust nothing the model returns
Three fences between the model and the database:

1. **Request-side**: `responseSchema` with enum-constrained `category` /
   `priority`, temperature 0.2, JSON-only responses.
2. **Prompt-side**: the tenant's message is framed as untrusted data — the
   prompt instructs the model to ignore any instructions embedded in it.
3. **Response-side**: `WorkOrderResponseValidator` turns raw model output into
  `AIWorkOrderDTO` and rejects missing/empty fields or unknown enum values, so
  a half-filled work order can never be persisted.

The classify cycle runs at most twice; failures log a warning with the reason
and nothing is saved.

### One Gemini client, one AI service
The HTTP transport (timeouts, retries, 429 mapping, empty-answer detection)
lives in a shared `GeminiClient`; `AIService` owns the domain flows for work
order generation and building summaries, while prompt builders keep the
prompt text out of the service itself. `WorkOrderResponseValidator` keeps the
structured output checks separate from the transport. The classification-
specific exception was generalised to `AiServiceException` at the same time —
one error surface for every AI failure.

The building summary path deliberately sets no `maxOutputTokens`: Gemini 2.5
models spend output budget on internal "thinking" before the visible text, so
a low cap truncates the answer mid-sentence. The prompt keeps summaries short
instead.

### Failure modes map to distinct status codes
- Client floods our API → route `throttle:10,1` → **429** (each request can
  spend AI quota, so the POST endpoint is deliberately modest).
- Gemini's own quota trips → **429 + Retry-After: 60**, and the second classify
  attempt is skipped (retrying a rate-limited call only burns more quota).
- Gemini unreachable / unusable answer → **502** with a calm, generic message;
  the real reason goes to the logs, not the client.

## Infrastructure (Local build)

### Docker: nginx + PHP-FPM + MySQL 8.4 + Redis 7
Four containers on one bridge network. nginx serves `public/` and proxies PHP
to the FPM container; MySQL has a healthcheck so the app container waits for it;
named volumes persist MySQL and Redis data.

### Two env examples
- `.env.example` — local, dependency-free profile: SQLite, file cache/session,
  sync queue.
- `.env.docker.example` — Docker profile: MySQL (`mysql` host), Redis cache,
  session and queue (`redis` host), compose port variables.

### Makefile as the task runner
Wraps the docker compose workflow (`make up`, `make migrate`, `make test`, …)
so the "running in under ten minutes" requirement holds without memorising
compose commands. npm targets were removed since this is an API-only server.
