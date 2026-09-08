<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class AccountOrderController extends Controller
{
    public function index(Request $request): View
    {
        $orders = $request->user()
            ->orders()
            ->latest()
            ->paginate(10);

        return view('account.orders.index', ['orders' => $orders]);
    }

    /**
     * Strictly owner-only — unlike the public /orders/{uuid} receipt link,
     * this account-area route never serves guest orders. A mismatch
     * returns 404 (not 403) so it doesn't confirm the order even exists.
     */
    public function show(Request $request, Order $order): View
    {
        abort_unless($order->user_id === $request->user()->id, 404);

        $order->load(['items', 'statusHistories']);

        return view('account.orders.show', ['order' => $order]);
    }
}
