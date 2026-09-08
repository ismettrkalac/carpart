@php
    /** @var \App\Models\Order $order */
@endphp

<div class="grid grid-cols-1 gap-6 lg:grid-cols-2" style="margin-top: 1rem;">
    <div>
        <h3 class="font-semibold" style="margin-bottom: .5rem;">Shipping address</h3>
        <p style="line-height: 1.6;">
            {{ $order->shipping_name }}<br>
            {{ $order->shipping_line1 }}<br>
            @if ($order->shipping_line2)
                {{ $order->shipping_line2 }}<br>
            @endif
            {{ $order->shipping_city }}, {{ $order->shipping_state }} {{ $order->shipping_postal_code }}<br>
            {{ $order->shipping_country }}
        </p>
    </div>
    <div>
        <h3 class="font-semibold" style="margin-bottom: .5rem;">Billing address</h3>
        <p style="line-height: 1.6;">
            {{ $order->billing_name }}<br>
            {{ $order->billing_line1 }}<br>
            @if ($order->billing_line2)
                {{ $order->billing_line2 }}<br>
            @endif
            {{ $order->billing_city }}, {{ $order->billing_state }} {{ $order->billing_postal_code }}<br>
            {{ $order->billing_country }}
        </p>
    </div>
</div>

<div style="margin-top: 1.5rem;">
    <h3 class="font-semibold" style="margin-bottom: .5rem;">Items</h3>
    <table style="width: 100%; border-collapse: collapse;">
        <thead>
            <tr style="text-align: left; border-bottom: 1px solid rgba(128,128,128,.3);">
                <th style="padding: .5rem 0;">Name</th>
                <th style="padding: .5rem 0;">SKU</th>
                <th style="padding: .5rem 0; text-align: right;">Qty</th>
                <th style="padding: .5rem 0; text-align: right;">Unit price</th>
                <th style="padding: .5rem 0; text-align: right;">Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($order->items as $item)
                <tr style="border-bottom: 1px solid rgba(128,128,128,.15);">
                    <td style="padding: .5rem 0;">{{ $item->name }}</td>
                    <td style="padding: .5rem 0;">{{ $item->sku }}</td>
                    <td style="padding: .5rem 0; text-align: right;">{{ $item->quantity }}</td>
                    <td style="padding: .5rem 0; text-align: right;">{{ \Illuminate\Support\Number::currency($item->unit_price_cents / 100, in: $order->currency) }}</td>
                    <td style="padding: .5rem 0; text-align: right;">{{ $item->formattedLineTotal($order->currency) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div style="margin-top: .75rem; max-width: 260px; margin-left: auto; font-size: .9rem;">
        <div style="display: flex; justify-content: space-between;"><span>Subtotal</span><span>{{ $order->formattedSubtotal() }}</span></div>
        <div style="display: flex; justify-content: space-between;"><span>Shipping</span><span>{{ $order->formattedShipping() }}</span></div>
        <div style="display: flex; justify-content: space-between;"><span>Tax</span><span>{{ $order->formattedTax() }}</span></div>
        <div style="display: flex; justify-content: space-between; font-weight: 600; border-top: 1px solid rgba(128,128,128,.3); padding-top: .25rem;">
            <span>Total</span><span>{{ $order->formattedTotal() }}</span>
        </div>
    </div>
</div>

<div style="margin-top: 1.5rem;">
    <h3 class="font-semibold" style="margin-bottom: .5rem;">Shipment</h3>
    @if ($order->carrier || $order->tracking_number || $order->tracking_url)
        <p style="font-size: .9rem;">
            @if ($order->carrier) Carrier: {{ $order->carrier }}<br> @endif
            @if ($order->tracking_number) Tracking #: {{ $order->tracking_number }}<br> @endif
            @if ($order->tracking_url) <a href="{{ $order->tracking_url }}" target="_blank" rel="noopener noreferrer">{{ $order->tracking_url }}</a> @endif
        </p>
    @else
        <p style="font-size: .9rem; opacity: .7;">No shipment details recorded yet.</p>
    @endif
</div>

<div style="margin-top: 1.5rem;">
    <h3 class="font-semibold" style="margin-bottom: .5rem;">Status history</h3>
    @if ($order->statusHistories->isEmpty())
        <p style="font-size: .9rem; opacity: .7;">No history yet.</p>
    @else
        <ul style="font-size: .9rem; list-style: none; padding: 0;">
            @foreach ($order->statusHistories as $entry)
                <li style="padding: .4rem 0; border-bottom: 1px solid rgba(128,128,128,.15);">
                    <strong>{{ $entry->status_type->value }}</strong>:
                    {{ $entry->from_status ?? 'created' }} → {{ $entry->to_status }}
                    <span style="opacity: .7;">
                        · {{ $entry->actorLabel() }} · {{ $entry->created_at->format('M j, Y g:i A') }}
                    </span>
                    @if ($entry->note)
                        <br><span style="opacity: .8;">{{ $entry->note }}</span>
                    @endif
                </li>
            @endforeach
        </ul>
    @endif
</div>

<div style="margin-top: 1.5rem;">
    <h3 class="font-semibold" style="margin-bottom: .5rem;">Internal notes <span style="font-weight: 400; opacity: .7; font-size: .8rem;">(staff only — never shown to the customer)</span></h3>
    @if ($order->notes->isEmpty())
        <p style="font-size: .9rem; opacity: .7;">No notes yet.</p>
    @else
        <ul style="font-size: .9rem; list-style: none; padding: 0;">
            @foreach ($order->notes as $note)
                <li style="padding: .5rem 0; border-bottom: 1px solid rgba(128,128,128,.15);">
                    {{ $note->body }}
                    <div style="opacity: .7; font-size: .8rem;">
                        {{ $note->author?->name ?? 'Staff' }} · {{ $note->created_at->format('M j, Y g:i A') }}
                    </div>
                </li>
            @endforeach
        </ul>
    @endif
</div>
