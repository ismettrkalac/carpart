<x-mail::message>
# Thanks for your order!

We've received your order **#{{ $order->orderNumber() }}** and will email you again once it ships.

<x-mail::table>
| Item | Qty | Total |
| :--- | :-: | ----: |
@foreach ($order->items as $item)
| {{ $item->name }} | {{ $item->quantity }} | {{ $item->formattedLineTotal($order->currency) }} |
@endforeach
</x-mail::table>

**Subtotal:** {{ $order->formattedSubtotal() }}<br>
**Shipping:** {{ $order->formattedShipping() }}<br>
**Tax:** {{ $order->formattedTax() }}<br>
**Total:** {{ $order->formattedTotal() }}

**Shipping to:**<br>
{{ $order->shipping_name }}<br>
{{ $order->shipping_line1 }}<br>
@if ($order->shipping_line2)
{{ $order->shipping_line2 }}<br>
@endif
{{ $order->shipping_city }}, {{ $order->shipping_state }} {{ $order->shipping_postal_code }}<br>
{{ $order->shipping_country }}

<x-mail::button :url="route('orders.show', $order)">
View your order
</x-mail::button>

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
