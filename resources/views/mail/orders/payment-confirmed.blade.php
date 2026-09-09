<x-mail::message>
# Payment received

We've received payment for order **#{{ $order->orderNumber() }}** — thanks!

**Total paid:** {{ $order->formattedTotal() }}

We'll email you again once your order ships.

<x-mail::button :url="route('orders.show', $order)">
View your order
</x-mail::button>

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
