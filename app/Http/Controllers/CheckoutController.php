<?php

namespace App\Http\Controllers;

use App\Http\Requests\CheckoutRequest;
use App\Models\Order;
use App\Services\Cart\CartService;
use App\Services\Checkout\CheckoutCalculator;
use App\Services\Checkout\CheckoutSnapshot;
use App\Services\Orders\OrderService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CheckoutController extends Controller
{
    /**
     * Session key holding a bounded map of token => snapshot, rather than a
     * single overwritable slot. A plain single slot breaks under anything
     * that causes more than one GET /checkout in flight for the same
     * session — e.g. the browser prerendering/prefetching the "Proceed to
     * Checkout" link before it's actually clicked, or two tabs — since
     * whichever request's snapshot is written last would silently
     * invalidate the token the shopper is actually about to submit.
     */
    private const SESSION_KEY = 'checkout_snapshots';

    private const MAX_STORED_SNAPSHOTS = 5;

    public function __construct(
        private readonly CartService $cart,
        private readonly CheckoutCalculator $calculator,
        private readonly OrderService $orders,
    ) {}

    public function create(Request $request): View|RedirectResponse
    {
        $items = $this->cart->items();

        if ($items->isEmpty()) {
            return redirect()->route('cart.index')->with('status', 'Your cart is empty.');
        }

        if ($this->cart->hasItemsNeedingAttention()) {
            return redirect()->route('cart.index')
                ->withErrors(['cart' => 'Please resolve the highlighted items in your cart before checking out.']);
        }

        $totals = $this->calculator->calculate($items);
        $token = (string) Str::uuid();

        $this->storeSnapshot($request, CheckoutSnapshot::capture($token, $items, $totals));

        return view('checkout.create', [
            'items' => $items,
            'totals' => $totals,
            'checkoutToken' => $token,
            'changes' => [],
        ]);
    }

    public function store(CheckoutRequest $request): View|RedirectResponse
    {
        $token = (string) $request->validated('checkout_token');

        // Already-processed duplicate: double-click, browser back + resubmit,
        // or a network retry of a request that actually succeeded. Checked
        // BEFORE looking at the cart, since the original successful request
        // already cleared it — a duplicate legitimately arrives with an
        // empty cart. Send them straight to the receipt instead of either
        // creating a second order or bouncing them to an "empty cart" page.
        $existingOrder = Order::where('idempotency_key', $token)->first();
        if ($existingOrder !== null) {
            return redirect()->route('orders.show', $existingOrder);
        }

        $items = $this->cart->items();

        if ($items->isEmpty()) {
            return redirect()->route('cart.index')->with('status', 'Your cart is empty.');
        }

        $snapshotData = $this->retrieveSnapshot($request, $token);
        $totals = $this->calculator->calculate($items);

        if ($snapshotData === null) {
            return redirect()->route('checkout.create')
                ->withErrors(['checkout' => 'Your checkout session expired. Please review your order and try again.']);
        }

        $snapshot = CheckoutSnapshot::fromSessionArray($snapshotData);
        $changes = $snapshot->diff($items, $totals);

        if ($this->cart->hasItemsNeedingAttention() || $changes !== []) {
            $newToken = (string) Str::uuid();
            $this->storeSnapshot($request, CheckoutSnapshot::capture($newToken, $items, $totals));
            $request->flashExcept(['checkout_token']);

            return view('checkout.create', [
                'items' => $items,
                'totals' => $totals,
                'checkoutToken' => $newToken,
                'changes' => $changes !== [] ? $changes : ['Some items in your cart need attention — please review below.'],
            ]);
        }

        $order = $this->orders->create(
            cartItems: $items,
            totals: $totals,
            email: (string) $request->validated('email'),
            shipping: $request->shippingAddress(),
            billing: $request->billingAddress(),
            userId: $request->user()?->id,
            idempotencyKey: $token,
        );

        $this->cart->clear();
        $this->forgetSnapshot($request, $token);

        return redirect()->route('orders.show', $order);
    }

    private function storeSnapshot(Request $request, CheckoutSnapshot $snapshot): void
    {
        $snapshots = $request->session()->get(self::SESSION_KEY, []);
        $snapshots[$snapshot->token] = $snapshot->toSessionArray();

        if (count($snapshots) > self::MAX_STORED_SNAPSHOTS) {
            $snapshots = array_slice($snapshots, -self::MAX_STORED_SNAPSHOTS, preserve_keys: true);
        }

        $request->session()->put(self::SESSION_KEY, $snapshots);
    }

    /**
     * @return array<string, mixed>|null
     */
    private function retrieveSnapshot(Request $request, string $token): ?array
    {
        return $request->session()->get(self::SESSION_KEY.'.'.$token);
    }

    private function forgetSnapshot(Request $request, string $token): void
    {
        $request->session()->forget(self::SESSION_KEY.'.'.$token);
    }
}
