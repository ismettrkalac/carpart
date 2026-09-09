# CarParts

A demo B2B/B2C auto-parts storefront built on Laravel 13 / PHP 8.4. It covers a parts catalog with VIN-based fitment lookup, a cart/checkout flow integrated with Paysera Checkout, customer order tracking, and a staff administration panel — all backing onto the same set of shared services.

> Demo data only — this is not a production storefront.

## Features

- **Parts catalog** — filterable by category and manufacturer, with business-specific pricing tiers, a per-part photo gallery, and relevance-ranked full-text search over name/SKU/description via [Laravel Scout](https://laravel.com/docs/scout) + [Meilisearch](https://www.meilisearch.com). See [Search](#search) below.
- **VIN lookup** — decodes a VIN via the [NHTSA vPIC API](https://vpic.nhtsa.dot.gov/api) to surface compatible fitments, with response caching.
- **Cart & checkout** — session-based cart with live stock/price revalidation at checkout; guest and authenticated checkout both supported. Every order is created `pending_payment`; if [Paysera](#payments) is configured, checkout redirects to a hosted Paysera Checkout session, otherwise it falls back to an unpaid receipt.
- **Payments** — [Paysera](https://developers.paysera.com) Checkout (hosted/redirect, chosen for Balkan regional coverage that Stripe lacks), including staff-initiated full refunds. See [Payments](#payments) below.
- **Order notifications** — queued confirmation, payment-received, shipped, and refunded emails, sent from the same services that own each state transition (never from a controller directly).
- **Customer accounts** — email/password registration, login, and password reset (`/forgot-password`, `/reset-password/{token}`); a `/account` profile hub links out to a customer's own orders (status timeline, item snapshot, shipment info) and a saved address book (multiple addresses, one default, reused to prefill checkout). Ownership is enforced on every request — an order or address's ID never grants access to another customer's data.
- **Guest order receipts** — a guest checkout gets a `/orders/{uuid}` link; the UUID itself is the unguessable access token, not the order number.
- **Staff admin panel** (`/admin`, via [MoonShine](https://moonshine-laravel.com)) — policy-guarded order management (searchable/paginated order table, payment/fulfillment/date filters, item snapshots, chronological status history, internal staff notes never shown to customers, manual shipment/tracking fields, status-transition and refund actions — order deletion and ad-hoc order creation are disabled by policy) plus full catalog management for parts (including photo galleries), categories, manufacturers, and suppliers.
- **Shared business rules** — all fulfillment-status transitions, payment gating, concurrency protection, and idempotency live in `App\Services\Orders`/`App\Services\Payments`, used identically by the MoonShine panel and any future customer/API surface (see [Architecture](#architecture)).

## Requirements

- PHP 8.4+
- Composer
- Node 20+ and npm
- MySQL/MariaDB (or SQLite for quick local use)
- [ddev](https://ddev.com) (recommended — this project is developed against it) or any equivalent local PHP environment
- [Meilisearch](https://www.meilisearch.com) for catalog search — provisioned automatically via ddev (see [Search](#search)), or run it yourself and point `MEILISEARCH_HOST`/`MEILISEARCH_KEY` at it

## Setup

```bash
composer install
npm install
cp .env.example .env
php artisan key:generate
```

Configure your database connection in `.env` (or leave `DB_CONNECTION=sqlite` for a quick local file-based setup), then run migrations:

```bash
php artisan migrate
npm run build
```

Once Meilisearch is up (see [Search](#search) below) and you have some parts in the database, set it up in two steps:

```bash
php artisan scout:sync-index-settings
php artisan scout:import "App\Models\Part"
```

`scout:sync-index-settings` pushes the `filterableAttributes`/`sortableAttributes` config from `config/scout.php` to the Meilisearch index — skip it and category/manufacturer filtering and sorting will fail with a "not filterable" error even though search itself works. `scout:import` backfills the index with existing parts.

`scout.queue` is enabled (see [Search](#search)), so `scout:import` queues the actual indexing work rather than doing it inline — either have a queue worker running first (`composer run dev` starts one), or set `SCOUT_QUEUE=false` in `.env` (not as a one-off shell prefix — under ddev, `SCOUT_QUEUE=false ddev artisan ...` sets the variable in your host shell, not inside the container, so it has no effect) to force it synchronous, then revert that once you have a worker running.

If you're using ddev, prefix the PHP/Composer/artisan commands with `ddev` (e.g. `ddev artisan migrate`, `ddev composer install`), but **always run `npm install`/`npm run build`/`npm run dev` on the host, never via `ddev exec`**. `node_modules` is shared between host and container, and Vite's bundler ships OS/architecture-specific native binaries — whichever side last ran `npm install` silently breaks the other side's build. Stick to one side (the host) and this never comes up.

### Local development

```bash
composer run dev
```

This runs the Laravel server, queue listener, log tailer (Pail), and Vite dev server together. Alternatively run `php artisan serve` and `npm run dev` in separate terminals.

## Granting staff admin access

Staff accounts (`moonshine_users`) are entirely separate from customer accounts (`users`) — there is no shared login. Create a staff account interactively so no credentials are ever hardcoded or committed:

```bash
php artisan moonshine:user
```

Follow the prompts to set an email, name, and password, then sign in at `/admin`. Authorization is enforced server-side via `App\Policies\OrderPolicy` — a customer's `web`-guard session grants no access to `/admin` regardless of what they're logged in as.

## Testing

```bash
php artisan test
```

or directly via PHPUnit:

```bash
vendor/bin/phpunit
```

Pass a path or `--filter=testName` to run a narrower slice, e.g. `php artisan test tests/Feature/OrderFulfillmentTest.php`.

Notable coverage:
- `tests/Feature/AdminAccessTest.php` — staff-only access to `/admin`, policy-level denial of delete/create.
- `tests/Feature/AccountOrdersTest.php` — customer order ownership, cross-customer/guest-order isolation.
- `tests/Feature/OrderFulfillmentTest.php` — valid/invalid status transitions, unpaid-order fulfillment restrictions, idempotent repeated actions, internal-note privacy, stock unaffected by fulfillment actions.
- `tests/Feature/PasswordResetTest.php` — forgot/reset-password flow, including the same response regardless of whether an email is registered.
- `tests/Feature/Paysera{Checkout,Webhook}Test.php`, `tests/Unit/{PayseraSignature,StockDeductionService}Test.php` — checkout redirect + fallback when unconfigured, webhook signature verification, idempotent/concurrency-safe payment confirmation, and stock decrement (including duplicate-delivery and negative-stock edge cases). All run against a faked HTTP client — no live Paysera credentials needed.
- `tests/Feature/CartTest.php`, `CheckoutTest.php`, `CatalogTest.php`, `VinLookupTest.php`, `OrderAccessTest.php` — catalog, cart, checkout, and guest-order-access behavior.

## Architecture

```
app/
├── Http/Controllers/          # Storefront, Account, Auth, and admin-adjacent controllers
├── Models/                    # Eloquent models (Order, OrderItem, OrderNote, OrderStatusHistory, Part, User, ...)
├── Policies/                  # Authorization — OrderPolicy serves both customer and staff contexts
├── Services/
│   ├── Cart/                  # Cart contents, revalidation against live price/stock
│   ├── Checkout/              # Totals calculation, address handling
│   ├── Inventory/             # Stock checks, time-boxed reservations, post-payment stock decrement
│   ├── Orders/                # Order creation + all fulfillment/shipment/note business rules
│   ├── Payments/              # Paysera Checkout integration (sessions, webhook, signature verification)
│   └── Vpic/                  # VIN decoding client + caching
└── MoonShine/                 # Staff admin panel: resources, pages, layout
```

The `App\Services\Orders` namespace is the single source of truth for order rules — it is not aware of MoonShine or HTTP, and is used identically by admin actions and customer-facing code:

- `OrderService` — creates orders (always `pending_payment`/`unfulfilled`), idempotent on a per-checkout key.
- `OrderFulfillmentService` — the fulfillment-status state machine (`unfulfilled → processing → shipped → delivered`, plus `cancelled`). Enforces that `processing`/`shipped` require `payment_status = paid`, uses a pessimistic lock + DB transaction per transition, is idempotent (repeating a transition is a safe no-op), and records every change in `OrderStatusHistory` with the acting actor and timestamp.
- `OrderNoteService` — internal staff notes, stored separately from status history and never rendered on any customer-facing view.
- `OrderShipmentService` — carrier/tracking-number/tracking-URL updates; rejects non-HTTPS tracking URLs. Tracking is manually maintained — there is no carrier API integration.

Payment status (`pending_payment`, `paid`, ...) and fulfillment status are deliberately independent columns/enums, so payment and shipping progress can never be conflated.

## Payments

Payments go through [Paysera Checkout](https://developers.paysera.com/guides/checkout-modern) — chosen over Stripe specifically because Stripe doesn't support merchant accounts in a number of Balkan countries. Integration is entirely in `App\Services\Payments`, over plain HTTP (Laravel's `Http` client, not an SDK) so it's testable with `Http::fake()` and carries no extra Composer dependency:

- `PayseraCheckoutService` — OAuth2 `client_credentials` auth (token cached for its ~1 hour lifetime), creates a Paysera order + hosted payment link, and applies a verified webhook event to the order it references.
- `PayseraSignature` — verifies the `X-Paysera-Signature` webhook header (HMAC-SHA256 of the raw body, keyed with your client secret), with an added replay-tolerance check on `X-Paysera-Created-At`.
- Checkout is **hosted/redirect-based**: the customer enters their card on Paysera's own page, which never touches this app. That keeps the integration eligible for **PCI DSS SAQ A**, the lightest self-assessment tier — embedding a card-entry widget directly on this app's own checkout page instead would require the much larger SAQ A-EP. (Being PCI compliant still requires completing that self-assessment with your own Paysera merchant account — this app's architecture only makes that possible at the lightest tier, it doesn't complete it for you.)
- Not configured out of the box: leave `PAYSERA_CLIENT_ID`/`PAYSERA_CLIENT_SECRET` blank in `.env` and checkout falls back to the pre-Paysera unpaid "order received" receipt, so the site works without any payment credentials at all. See [Granting staff admin access](#granting-staff-admin-access)-style setup — create a Paysera account and a Checkout (Modern) project at https://developers.paysera.com, then put its `client_id`/`client_secret` in your own `.env`.
- Paysera's webhook payload doesn't publish a formal `status` enum for orders (confirmed against their OpenAPI spec) — payment confirmation is decided from the numeric `order.amount`/`order.amount_paid` fields instead of a status string. Worth double-checking against a real sandbox webhook payload if Paysera's response shape ever changes.
- **Stock is deducted only once payment is confirmed** — `App\Services\Inventory\StockDeductionService::deductForOrder()`, called from inside the same locked transaction that flips `payment_status` to `Paid` in `PayseraCheckoutService::handleWebhookEvent()`. A duplicate webhook delivery (Paysera, like most providers, only guarantees at-least-once delivery) can't double-decrement, since the order row is locked before its payment status is checked.
- **Stock is reserved while an order is pending payment** — `App\Services\Inventory\StockReservationService::reserveForOrder()` creates a time-boxed hold (`checkout.reservation_minutes`, default 30) on each item's quantity the moment an order is placed, closing the window between checkout-session creation and payment confirmation where the last unit could otherwise be oversold. `StockChecker` subtracts active reservations from availability, so a second shopper's cart/checkout sees the held stock as unavailable. The reservation is released once payment is confirmed (`StockDeductionService`, since the units are then actually decremented) or if the order is cancelled first (`OrderFulfillmentService`); an abandoned checkout's hold simply expires on its own.

## Search

Catalog search (name/SKU/description, with category/manufacturer filters and sorting) goes through [Laravel Scout](https://laravel.com/docs/scout) with the [Meilisearch](https://www.meilisearch.com) driver, replacing what used to be a plain `LIKE` query.

- `Part` uses the `Laravel\Scout\Searchable` trait. `Part::shouldBeSearchable()` means only `active` parts are ever indexed — a draft or discontinued part can't turn up in results no matter what's searched. `PartController` also applies an explicit `status = active` filter on top of that (belt and suspenders — and it's what keeps the test suite's `database` Scout driver, which has no separate index to have excluded anything from in the first place, behaving the same way).
- Filterable/sortable attributes (`category_id`, `manufacturer_id`, `status`, `base_price_cents`, `name`, `created_at`) are configured in `config/scout.php` — but that config only takes effect once you actually run `php artisan scout:sync-index-settings`. Without it, search itself works but filtering by category/manufacturer/status and sorting by price/name will fail with a "not filterable"/"not sortable" error, since Meilisearch doesn't know about those attributes yet.
- **ddev**: `.ddev/` isn't committed to this repo (see `.gitignore` — every developer's ddev config is local-only, same as `.idea`/`.vscode`/etc.), so there's no service file to pull in. Add one yourself at `.ddev/docker-compose.meilisearch.yaml`:

  ```yaml
  services:
    meilisearch:
      container_name: ddev-${DDEV_SITENAME}-meilisearch
      image: getmeili/meilisearch:v1.10
      restart: "no"
      environment:
        - MEILI_ENV=development
        - MEILI_MASTER_KEY=${MEILISEARCH_KEY:-carpart_local_dev_key}
        - MEILI_NO_ANALYTICS=true
        - VIRTUAL_HOST=$DDEV_HOSTNAME
        - HTTP_EXPOSE=7700:7700
      volumes:
        - meilisearch_data:/meili_data
      labels:
        com.ddev.site-name: ${DDEV_SITENAME}
        com.ddev.approot: $DDEV_APPROOT

  volumes:
    meilisearch_data:
  ```

  Then `ddev restart` to pick it up — it becomes reachable from the app container at `MEILISEARCH_HOST=http://meilisearch:7700` (already set in `.env.example`). Follow [Setup](#setup) above (`scout:sync-index-settings`, then `scout:import`) to configure and backfill the index. New/updated parts after that stay in sync automatically in the background via the queue — so indexing never blocks a request, but it does mean a queue worker (`composer run dev` starts one) needs to be running for a newly-added part to actually become searchable. A shell-prefixed env override like `SCOUT_QUEUE=false ddev artisan ...` does **not** reach the container (it only sets the variable in your host shell) — set it in `.env` directly instead if you need to force synchronous indexing.
- **Not configured / Meilisearch unreachable**: unlike Paysera and VIN lookup, there's no graceful fallback here — a search request will fail if `SCOUT_DRIVER=meilisearch` and nothing is listening at `MEILISEARCH_HOST`. Set `SCOUT_DRIVER=database` (Scout's built-in driver, no external service needed — see `phpunit.xml`, which does exactly this for the test suite) if you want the site to run without Meilisearch.
- The ddev service definition follows Meilisearch's/ddev's documented conventions but hasn't been exercised against a real `ddev start` from this environment — worth confirming it comes up cleanly the first time you run it, same caution as the Paysera integration notes above about unverified third-party specifics.

## Known limitations

- Shipment tracking is manual entry only; no carrier API or live delivery updates.
- Refunds are full-only — there's no partial-refund support, even though Paysera's API allows it (see [Payments](#payments)).
- Checkout only ever prefills from a customer's *default* saved address; there's no picker to choose a different one at checkout without first changing the default on the account page.
