<?php

namespace App\Http\Controllers;

use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Services\Payments\PayseraCheckoutService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

class OrderController extends Controller
{
    public function __construct(private readonly PayseraCheckoutService $paysera) {}

    public function show(Order $order): View
    {
        Gate::authorize('view', $order);

        $order->load('items');

        return view('orders.show', ['order' => $order]);
    }

    /**
     * Starts (or restarts) a Paysera Checkout session for an order still
     * awaiting payment — e.g. the customer closed Paysera's page without
     * paying and wants to try again from their receipt.
     */
    public function pay(Order $order): RedirectResponse
    {
        Gate::authorize('view', $order);

        abort_unless($order->payment_status === PaymentStatus::PendingPayment, 404);
        abort_unless($this->paysera->isConfigured(), 404);

        return redirect()->away($this->paysera->createSession($order));
    }
}
