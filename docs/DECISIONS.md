# Design decisions

Plain-language notes on why OrderDesk is built the way it is. Updated every phase.

## Phase 1 – data model

**Money is stored as integer cents.** Floats cannot represent most decimal amounts exactly (`0.1 + 0.2 != 0.3`), which is unacceptable for invoices. Every price and total column is an unsigned integer number of cents; formatting to "12.50" happens only at the edge (views, API). One currency keeps the demo simple.

**Tax rate is stored per quote/order in basis points** (1900 = 19.00%). It is also an integer, and storing it on the document means changing a rate later never rewrites history. Tax is computed once on the subtotal and rounded half-up (`TotalsCalculator`), not per line, so the numbers match what a customer would recompute from the totals.

**Line items are snapshots.** `quote_items` / `order_items` copy the description and unit price instead of reading the product live. If a product's price changes next month, old quotes and orders must still show what was agreed. `product_id` stays as a nullable link (set to null if the product is deleted).

**Status is a backed enum with its own transition rules.** `QuoteStatus` (draft → sent → accepted | rejected) and `OrderStatus` (draft → confirmed → shipped; draft/confirmed → cancelled) expose `canTransitionTo()`. The rules live in one place and are unit-tested without a database; services will call them so web and API behave identically.

**One quote becomes at most one order.** `orders.quote_id` is `UNIQUE`, so the database itself rejects a double conversion even if two requests race. The order keeps the link (null on quote deletion) for traceability.

**Foreign keys restrict deletion** of customers and users that have documents, rather than cascading, to avoid silently destroying business records. Items cascade with their parent document.

**Sanctum** (token auth for the API) is installed now so its `personal_access_tokens` migration is part of the schema from day one.

**Factories and a seeder produce fake data only.** `DemoSeeder` creates a demo login (`demo@orderdesk.test` / `password`), a realistic product catalogue, customers, and quotes/orders in every status. A feature test checks that seeded totals are internally consistent.

**Tests use SQLite in memory** (fast, no Docker needed); CI will additionally run against MySQL in phase 4.

## Phase 2 – web UI and services

**Controllers are thin; services own the rules.** A controller validates (Form Request), calls one service method and redirects. `QuoteService` (create/update/delete/status), `QuoteConversionService` (quote → order), `OrderService` (status changes) and `CustomerService` (delete guard) hold all business logic. The REST API in phase 3 will call the same services, so web and API cannot drift apart.

**Illegal actions throw `DomainRuleViolation`.** One exception type with named constructors (`quoteNotEditable()`, `orderTransition()`…) and user-safe messages. `bootstrap/app.php` renders it as a flash error for the web and a `422` JSON message for the API, so controllers need no try/catch.

**Status changes re-read the row with `lockForUpdate()` inside a transaction.** Two people clicking at once cannot both pass the "is this transition allowed?" check on stale data. Conversion adds a second guard: `orders.quote_id` is unique (phase 1), so even a bug in the service cannot create two orders for one quote.

**Quote → order copies, it does not reference.** The order gets its own item rows and totals copied from the accepted quote and starts as `draft`. Later quote or product changes cannot alter an order. Only *accepted* quotes convert, and accepted/rejected quotes are final.

**Document numbers (`Q-2026-00042`, `O-2026-00007`) are derived from the row id.** The row is inserted with a throw-away UUID number, then renamed inside the same transaction. This avoids the race of "read max number, add one" without a separate counter table. Numbers are unique and ordered, but not guaranteed gap-free (a deleted draft leaves a gap), which is fine for a demo.

**Quote lines choose a product and a quantity; the server snapshots name and price.** The browser never sends prices, so a tampered form cannot change them. Only active products can be quoted. Items are replaced wholesale on edit (simple and correct for ≤100 lines), and only draft quotes can be edited or deleted.

**Money in forms is a decimal string, converted by `App\Support\Money`.** Users type `12.50`; the Form Request converts to `1250` cents and rejects `12.345`. Tax is typed as a percentage and stored as basis points. The same helper formats cents for display through the `@money` Blade directive.

**Session login written by hand** (`LoginController`, no starter kit): fewer files to explain. Login is rate-limited to 5 attempts/minute per email+IP (`throttle:login`), and the session id is regenerated on login to prevent fixation. All other routes sit behind the `auth` middleware.

**No JavaScript framework.** Tailwind via Vite plus ~25 lines of vanilla JS to add/remove quote lines. `Model::preventLazyLoading()` is on outside production, so an accidental N+1 query fails tests instead of silently slowing pages.

**Testing without Vite:** `TestCase` calls `withoutVite()`, so tests need no `npm run build`.

## Phase 3 – REST API

**The API is a second front door to the same services.** API controllers (`App\Http\Controllers\Api\V1`) validate, call `QuoteService` / `QuoteConversionService` / `OrderService` / `CustomerService`, and return resources. No business rule exists twice, so the web UI and the API cannot disagree about what a legal status change is.

**Versioned under `/api/v1`.** A breaking change later can ship as `/api/v2` without breaking existing clients.

**Sanctum personal access tokens, not sessions or JWT.** `POST /api/v1/auth/token` exchanges email + password for a bearer token (stored hashed in `personal_access_tokens`); `DELETE /api/v1/auth/token` revokes it. Tokens expire after 30 days (`SANCTUM_TOKEN_EXPIRATION_MINUTES`). Sanctum is first-party, needs no extra moving parts, and tokens can be revoked individually, which a stateless JWT cannot do without extra storage. Wrong credentials give `401` with a deliberately vague message.

**Two rate limiters.** `login` (5/min per email + IP) protects password guessing on both the web login and the token endpoint; `api` (60/min per user, per IP if unauthenticated) bounds normal traffic. Both are named limiters in `AppServiceProvider`, so the numbers live in one place.

**API Resources define the public JSON shape**, separate from the database columns. Money is always integer cents with a `_cents` suffix and tax is `tax_rate_bp`, so clients never parse decimals. Relations (`customer`, `items`) appear only when the controller eager-loaded them (`whenLoaded`), which also keeps lazy loading from sneaking in.

**Pagination:** list endpoints use Laravel's paginator (`data`, `links`, `meta`). `?per_page=` defaults to 15 and is clamped to 1–100 so a client cannot ask for a million rows. Quotes and orders can be filtered with `?status=` and `?customer_id=`.

**Form Requests are reused, not copied.** The API `ProductRequest` and `QuoteRequest` extend the web ones and change only the one field that differs (price and tax rate as integers instead of form strings). Status changes use one `QuoteStatusRequest` / `OrderStatusRequest` for both front ends.

**Status codes follow the usual meaning:** `201` created, `204` deleted, `401` no/invalid token, `404` unknown id, `405` for things that do not exist (orders are created only by converting a quote, never directly, and are never deleted), `422` for validation errors *and* broken business rules (JSON `message`), `429` rate limited. Domain errors return JSON for every `/api/*` request even if the client forgot the `Accept` header.

**Orders are read-mostly.** The API can list, show and change status of orders, and create them only via `POST /quotes/{id}/convert`. That keeps the quote-to-order rule as the single way an order comes into being.
