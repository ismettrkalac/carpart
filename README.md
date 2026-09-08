# CarParts

A demo B2B/B2C auto-parts storefront built on Laravel 13 / PHP 8.4. It covers a parts catalog with VIN-based fitment lookup, a cart and unpaid checkout flow, customer order tracking, and a staff administration panel — all backing onto the same set of shared services.

> Demo data only — this is not a production storefront. Payments are intentionally not integrated yet (see [Payments](#payments) below).

## Features

- **Parts catalog** — searchable/filterable parts by category, manufacturer, and SKU, with business-specific pricing tiers.
- **VIN lookup** — decodes a VIN via the [NHTSA vPIC API](https://vpic.nhtsa.dot.gov/api) to surface compatible fitments, with response caching.
- **Cart & checkout** — session-based cart with live stock/price revalidation at checkout; guest and authenticated checkout both supported. Every order is created `pending_payment` — no stock is reserved or deducted until a real payment is integrated.
- **Customer accounts** — email/password registration and login; `/account/orders` lists a customer's own orders and shows a per-order status timeline, item snapshot, and shipment info. Ownership is enforced on every request — an order's ID never grants access to another customer's data.
- **Guest order receipts** — a guest checkout gets a `/orders/{uuid}` link; the UUID itself is the unguessable access token, not the order number.
- **Staff admin panel** (`/admin`, via [MoonShine](https://moonshine-laravel.com)) — policy-guarded order management: searchable/paginated order table, payment/fulfillment/date filters, order detail with item snapshots and address info, chronological status history, internal staff notes (never shown to customers), manual shipment/tracking fields, and explicit status-transition actions. Order deletion and ad-hoc order creation are disabled by policy; historical items and totals are read-only.
- **Shared business rules** — all fulfillment-status transitions, payment gating, concurrency protection, and idempotency live in `App\Services\Orders`, used identically by the MoonShine panel and any future customer/API surface (see [Architecture](#architecture)).

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

If you're using ddev, prefix the PHP/artisan commands with `ddev` (e.g. `ddev artisan migrate`, `ddev composer install`) and run `npm`/`ddev` commands from the host as shown in `.claude`/project docs.

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
│   ├── Inventory/             # Stock checks
│   ├── Orders/                # Order creation + all fulfillment/shipment/note business rules
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

This project does not integrate a payment provider. Every order is created `pending_payment` and stock is never reserved or deducted for an unpaid order. The seams for a future integration are documented inline:

- `App\Services\Orders\OrderService` — where a payment session/intent would be initiated after order creation, and where the order's snapshot totals feed into it.
- `App\Services\Orders\OrderFulfillmentService` — where a verified payment confirmation (e.g. a signature-checked webhook) would flip `payment_status` to `paid`, unblocking `processing`/`shipped` transitions.
- Stock reservation/deduction on payment confirmation is not implemented — see the inline notes in `OrderService`/`Inventory` services for where it should connect.

No provider-specific dependencies or placeholder integrations are included.

## Known limitations

- No payment provider integration (see above) — checkout ends at an unpaid order.
- Shipment tracking is manual entry only; no carrier API or live delivery updates.
- No email notifications (order confirmation, status-change, etc.) are sent yet.
