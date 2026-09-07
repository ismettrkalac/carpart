<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;

class OrderController extends Controller
{
    public function show(Order $order): View
    {
        Gate::authorize('view', $order);

        $order->load('items');

        return view('orders.show', ['order' => $order]);
    }
}
