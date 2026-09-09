<x-mail::message>
# Your order has shipped

Order **#{{ $order->orderNumber() }}** is on its way.

@if ($order->carrier)
**Carrier:** {{ $order->carrier }}<br>
@endif
@if ($order->tracking_number)
**Tracking number:** {{ $order->tracking_number }}
@endif

@if ($order->tracking_url)
<x-mail::button :url="$order->tracking_url">
Track your package
</x-mail::button>
@endif

<x-mail::button :url="route('orders.show', $order)">
View your order
</x-mail::button>

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
