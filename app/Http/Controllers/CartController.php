<?php

namespace App\Http\Controllers;

use App\Http\Requests\CartItemRequest;
use App\Models\Part;
use App\Services\Cart\CartException;
use App\Services\Cart\CartItem;
use App\Services\Cart\CartService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Number;

class CartController extends Controller
{
    public function __construct(private readonly CartService $cart) {}

    public function index(): View
    {
        $subtotalCents = $this->cart->subtotalCents();

        return view('cart.index', [
            'items' => $this->cart->items(),
            'subtotalCents' => $subtotalCents,
            'subtotalFormatted' => Number::currency($subtotalCents / 100),
        ]);
    }

    public function store(CartItemRequest $request, Part $part): RedirectResponse
    {
        try {
            $this->cart->add($part, (int) $request->validated('quantity'));
        } catch (CartException $exception) {
            return back()->withErrors(['quantity' => $exception->getMessage()])->withInput();
        }

        return back()->with('status', "Added \"{$part->name}\" to your cart.");
    }

    /**
     * Quantity changes on the cart page are debounced and submitted via
     * fetch() rather than a plain form post — a full-page reload on every
     * adjustment (and the scroll-to-top that comes with it) is what made
     * the cart feel "bouncy". A JSON request gets a JSON summary back
     * instead of a redirect; the plain-form/no-JS path is unchanged.
     */
    public function update(CartItemRequest $request, Part $part): RedirectResponse|JsonResponse
    {
        try {
            $this->cart->update($part->id, (int) $request->validated('quantity'));
        } catch (CartException $exception) {
            if ($request->wantsJson()) {
                return response()->json(['message' => $exception->getMessage()], 422);
            }

            return back()->withErrors(['quantity' => $exception->getMessage()])->withInput();
        }

        if ($request->wantsJson()) {
            $item = $this->cart->items()->firstWhere('partId', $part->id);

            return response()->json($this->summary($item));
        }

        return back()->with('status', 'Cart updated.');
    }

    /**
     * Plain int (not route-model-bound): removal must still work even if
     * the underlying Part record no longer exists.
     */
    public function destroy(Request $request, int $partId): RedirectResponse|JsonResponse
    {
        $this->cart->remove($partId);

        if ($request->wantsJson()) {
            return response()->json($this->summary());
        }

        return back()->with('status', 'Item removed from your cart.');
    }

    /**
     * @return array<string, mixed>
     */
    private function summary(?CartItem $item = null): array
    {
        $subtotalCents = $this->cart->subtotalCents();

        return [
            'isEmpty' => $this->cart->isEmpty(),
            'cartCount' => $this->cart->count(),
            'subtotalCents' => $subtotalCents,
            'subtotalFormatted' => Number::currency($subtotalCents / 100),
            'item' => $item === null ? null : [
                'lineTotalCents' => $item->lineTotalCents,
                'lineTotalFormatted' => $item->formattedLineTotal(),
                'needsAttention' => $item->needsAttention(),
            ],
        ];
    }
}
