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
