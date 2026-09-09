# CarParts

A demo B2B/B2C auto-parts storefront built on Laravel 13 / PHP 8.4. It covers a parts catalog with VIN-based fitment lookup, a cart/checkout flow integrated with Paysera Checkout, customer order tracking, and a staff administration panel — all backing onto the same set of shared services.

> Demo data only — this is not a production storefront.

## Features

- **Parts catalog** — searchable/filterable parts by category, manufacturer, and SKU, with business-specific pricing tiers.
- **VIN lookup** — decodes a VIN via the [NHTSA vPIC API](https://vpic.nhtsa.dot.gov/api) to surface compatible fitments, with response caching.
- **Cart & checkout** — session-based cart with live stock/price revalidation at checkout; guest and authenticated checkout both supported. Every order is created `pending_payment`; if [Paysera](#payments) is configured, checkout redirects to a hosted Paysera Checkout session, otherwise it falls back to an unpaid receipt.
- **Payments** — [Paysera](https://developers.paysera.com) Checkout (hosted/redirect, chosen for Balkan regional coverage that Stripe lacks). See [Payments](#payments) below.
- **Customer accounts** — email/password registration, login, and password reset (`/forgot-password`, `/reset-password/{token}`); `/account/orders` lists a customer's own orders and shows a per-order status timeline, item snapshot, and shipment info. Ownership is enforced on every request — an order's ID never grants access to another customer's data.
- **Guest order receipts** — a guest checkout gets a `/orders/{uuid}` link; the UUID itself is the unguessable access token, not the order number.
- **Staff admin panel** (`/admin`, via [MoonShine](https://moonshine-laravel.com)) — policy-guarded order management: searchable/paginated order table, payment/fulfillment/date filters, order detail with item snapshots and address info, chronological status history, internal staff notes (never shown to customers), manual shipment/tracking fields, and explicit status-transition actions. Order deletion and ad-hoc order creation are disabled by policy; historical items and totals are read-only.
- **Shared business rules** — all fulfillment-status transitions, payment gating, concurrency protection, and idempotency live in `App\Services\Orders`/`App\Services\Payments`, used identically by the MoonShine panel and any future customer/API surface (see [Architecture](#architecture)).

## Requirements

- PHP 8.4+
- Composer
- Node 20+ and npm
- MySQL/MariaDB (or SQLite for quick local use)
- [ddev](https://ddev.com) (recommended — this project is developed against it) or any equivalent local PHP environment

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

## Known limitations

- Shipment tracking is manual entry only; no carrier API or live delivery updates.
