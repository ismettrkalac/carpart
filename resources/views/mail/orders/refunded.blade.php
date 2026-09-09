<x-mail::message>
# Your order has been refunded

We've refunded order **#{{ $order->orderNumber() }}** in full.

**Amount refunded:** {{ $order->formattedTotal() }}

Refunds are typically returned to your original payment method within a few business days, depending on your bank or card issuer.

<x-mail::button :url="route('orders.show', $order)">
View your order
</x-mail::button>

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
