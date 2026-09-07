<?php

namespace App\Http\Controllers;

use App\Http\Requests\CartItemRequest;
use App\Models\Part;
use App\Services\Cart\CartException;
use App\Services\Cart\CartService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
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

    public function update(CartItemRequest $request, Part $part): RedirectResponse
    {
        try {
            $this->cart->update($part->id, (int) $request->validated('quantity'));
        } catch (CartException $exception) {
            return back()->withErrors(['quantity' => $exception->getMessage()])->withInput();
        }

        return back()->with('status', 'Cart updated.');
    }

    /**
     * Plain int (not route-model-bound): removal must still work even if
     * the underlying Part record no longer exists.
     */
    public function destroy(int $partId): RedirectResponse
    {
        $this->cart->remove($partId);

        return back()->with('status', 'Item removed from your cart.');
    }
}
