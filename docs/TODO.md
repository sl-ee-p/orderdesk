# To do

Known issues from the second review pass that are not fixed yet. Each entry says what is wrong, where it is, and the suggested fix.

## 1. Quote edit form hides lines whose product is gone

**Problem.** When a draft quote has a line whose product was later deactivated or deleted, the edit form shows that line as "Choose a product…" and gives no explanation. The server then refuses to save it (`items.N.product_id` must be an active product), so the user cannot tell which line is wrong or what it used to be.

**Where.** `resources/views/quotes/_form.blade.php` (the `$lines` `@php` block and the product `<select>`), `QuoteController::formData()` (it loads only active products), `resources/js/app.js` (the "add line" code clones the first row).

**Suggested fix.**
- Build the lines in `QuoteController` (or the view). Mark a line unavailable when its saved `product_id` is null (deleted) or is not among the active product ids. For old input, also mark a line unavailable when its `product_id` is filled but not active. Name the line from the item's snapshot `description`, or from a `product_id → description` map of the saved items when the line comes from old input.
- For an unavailable line, make the first `<option>` `value=""` (so `required` blocks submit) with the text "<name> (no longer available)". Below the select, add a notice the select points at with `aria-describedby`: "“<name>” is no longer available. Choose another product or remove this line."
- Have "add line" clone a `<template>` row and not the first line, so the notice and the special option are never copied.
- Test (`tests/Feature/QuoteTest.php`): a draft quote with one deactivated and one deleted product. `GET /quotes/{id}/edit` shows both descriptions and "no longer available".

## 2. Race on a duplicate customer email or product SKU gives a 500

**Problem.** `Rule::unique` is checked before the insert or update. If two requests save the same email or SKU at the same time, both pass validation, and the database's unique index then throws `UniqueConstraintViolationException`, which shows up as a 500 error.

**Where.** `CustomerController::store/update` (web and `Api\V1`), `ProductController::store/update` (web and `Api\V1`). For comparison, `CustomerService::delete()` already handles the foreign-key race.

**Suggested fix.**
- Move the writes into services: add `create`/`update` to `CustomerService`, and add a new `ProductService`.
- In those services, catch `Illuminate\Database\UniqueConstraintViolationException` and throw `ValidationException::withMessages(['email' => __('validation.unique', ['attribute' => 'email'])])` (use `sku` for products). Laravel detects this exception on both SQLite and MySQL. The web UI then redirects back with the field error, and the API returns a 422 with `errors`.
- Tests: simulate the race with a `Customer::creating` / `updating` listener (or `Product::...`) that inserts the duplicate with `Customer::factory()->createQuietly(['email' => ...])`. Flush the listeners in a `finally` block, as `CustomerTest::test_document_created_during_deletion_still_blocks_it` does. Assert the session error (web) and `assertJsonValidationErrors` (API). Add a DECISIONS entry.

## 3. The API does not link quotes and orders in both directions

**Problem.** An order exposes `quote_id`, but a quote does not say which order it became. Orders also cannot be filtered by quote.

**Where.** `app/Http/Resources/QuoteResource.php`, `Api\V1\QuoteController` (index/store/show/update), `Api\V1\QuoteStatusController`, `Api\V1\OrderController::index`, `ApiController::documentFilters()`, README "API" section.

**Suggested fix.**
- In `QuoteResource`, add `'order_id' => $this->whenLoaded('order', fn () => $this->order->id)`. This is null when the order relation is loaded but there is no order, and the key is left out when the relation is not loaded.
- Eager-load `order` everywhere quotes are returned: `with(['customer', 'order'])` and `load(['customer', 'items', 'order'])`.
- Give `documentFilters()` a list of id filters to validate as `integer`, with `['customer_id']` as the default. Have `OrderController` pass `['customer_id', 'quote_id']` and add `->when($filters['quote_id'] ?? null, ...)`.
- README: mention `order_id` on quotes and `?quote_id=` on orders, where invalid values return 422.
- Tests in `QuoteApiTest` (order_id is null before conversion and set after) and `OrderApiTest` (`?quote_id=` filters, `?quote_id=abc` returns 422).

## 4. The customer page lists documents in no fixed order

**Problem.** The quotes and orders on the customer page use `latest()` (created_at only). Rows created in the same second can therefore come back in any order.

**Where.** `CustomerController::show()`.

**Suggested fix.** Use `->latest()->latest('id')` for both lists. No other `latest()` call needs this: every other list already uses `latest('id')`, and name sorts are on the indexed `name` column.

Related, lower priority: the `Quote::items()` / `Order::items()` relations have no `orderBy`, so the order of lines on a document depends on the database. Adding `orderBy('id')` to the eager loads (not to the relation itself, because `items()->delete()` with ORDER BY needs care on SQLite) would make it fixed.

## 5. Inline `confirm()` handlers block a Content-Security-Policy

**Problem.** The delete forms use inline `onsubmit="return confirm(...)"`. A CSP without `'unsafe-inline'` would block these handlers, and so far there is no CSP.

**Where.** `resources/views/customers/show.blade.php`, `resources/views/products/edit.blade.php`, `resources/views/quotes/show.blade.php`, `resources/js/app.js`, `app/Http/Middleware/SetSecurityHeaders.php`.

**Suggested fix.**
- Replace each inline handler with `data-confirm="Delete this customer?"` (and the same for the other two forms). In `app.js`, add one delegated listener: `document.addEventListener('submit', (e) => { const message = e.target.dataset?.confirm; if (message && !confirm(message)) e.preventDefault(); })`.
- Then add the CSP in `SetSecurityHeaders`, the way the Laravel docs do it. Call `Vite::useCspNonce()` before `$next($request)`, then send roughly: `default-src 'self'; script-src 'self' 'nonce-…'; style-src 'self' 'nonce-…' https://fonts.bunny.net; font-src 'self' https://fonts.bunny.net; img-src 'self' data:; object-src 'none'; base-uri 'self'; form-action 'self'; frame-ancestors 'none'`.
- Skip the CSP when `app()->isLocal()` or `Vite::isRunningHot()`, so the Vite dev server keeps working.
- Check the fonts in a production build first. `vite.config.js` uses the `bunny()` font helper from laravel-vite-plugin, and it is not confirmed whether that self-hosts the fonts or links to fonts.bunny.net.
- Tests in `SecurityHeadersTest`: the header is present outside local and contains the nonce; the pages contain no `onsubmit=`.
- Update the DECISIONS entry, which currently says the CSP was left out.
