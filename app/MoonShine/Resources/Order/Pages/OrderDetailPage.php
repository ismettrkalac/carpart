<?php

declare(strict_types=1);

namespace App\MoonShine\Resources\Order\Pages;

use App\Enums\FulfillmentStatus;
use App\Enums\PaymentStatus;
use App\Models\Order;
use App\MoonShine\Resources\Order\OrderResource;
use App\Services\Orders\OrderFulfillmentService;
use MoonShine\Contracts\UI\ComponentContract;
use MoonShine\Contracts\UI\FieldContract;
use MoonShine\Laravel\Pages\Crud\DetailPage;
use MoonShine\Support\ListOf;
use MoonShine\UI\Components\ActionButton;
use MoonShine\UI\Components\FlexibleRender;
use MoonShine\UI\Fields\Enum;
use MoonShine\UI\Fields\ID;
use MoonShine\UI\Fields\Text;
use MoonShine\UI\Fields\Textarea;

/**
 * @extends DetailPage<OrderResource>
 */
class OrderDetailPage extends DetailPage
{
    /**
     * @return list<FieldContract>
     */
    protected function fields(): iterable
    {
        return [
            ID::make(),
            Text::make('Order #', 'uuid', fn (Order $order) => $order->orderNumber()),
            Text::make('Email', 'email'),
            Enum::make('Payment status', 'payment_status')->attach(PaymentStatus::class),
            Enum::make('Fulfillment status', 'fulfillment_status')->attach(FulfillmentStatus::class),
            Text::make('Total', 'total_cents', fn (Order $order) => $order->formattedTotal()),
            Text::make('Placed', 'created_at', fn (Order $order) => $order->created_at->format('M j, Y g:i A')),
        ];
    }

    protected function buttons(): ListOf
    {
        $service = app(OrderFulfillmentService::class);

        return parent::buttons()->add(
            ActionButton::make('Mark Processing')
                ->method('transitionOrderStatus', params: fn (Order $order) => [
                    'resourceItem' => $order->getKey(),
                    'to' => FulfillmentStatus::Processing->value,
                ])
                ->canSee(fn (Order $order) => in_array(FulfillmentStatus::Processing, $service->availableTransitions($order), true))
                ->withConfirm(
                    title: 'Mark as processing?',
                    content: 'The order will be shown as being prepared.',
                ),

            ActionButton::make('Mark Shipped')
                ->method('transitionOrderStatus', params: fn (Order $order) => [
                    'resourceItem' => $order->getKey(),
                    'to' => FulfillmentStatus::Shipped->value,
                ])
                ->canSee(fn (Order $order) => in_array(FulfillmentStatus::Shipped, $service->availableTransitions($order), true))
                ->withConfirm(title: 'Mark as shipped?'),

            ActionButton::make('Mark Delivered')
                ->method('transitionOrderStatus', params: fn (Order $order) => [
                    'resourceItem' => $order->getKey(),
                    'to' => FulfillmentStatus::Delivered->value,
                ])
                ->canSee(fn (Order $order) => in_array(FulfillmentStatus::Delivered, $service->availableTransitions($order), true))
                ->withConfirm(title: 'Mark as delivered?'),

            ActionButton::make('Cancel Order')
                ->method('cancelOrder', params: fn (Order $order) => ['resourceItem' => $order->getKey()])
                ->canSee(fn (Order $order) => in_array(FulfillmentStatus::Cancelled, $service->availableTransitions($order), true))
                ->withConfirm(
                    title: 'Cancel this order?',
                    content: 'This cannot be undone.',
                    fields: [Textarea::make('Reason (optional)', 'note')],
                ),

            ActionButton::make('Refund')
                ->method('refundOrder', params: fn (Order $order) => ['resourceItem' => $order->getKey()])
                ->canSee(fn (Order $order) => $order->payment_status === PaymentStatus::Paid)
                ->withConfirm(
                    title: 'Refund this order?',
                    content: 'Refunds the full amount via Paysera and cannot be undone.',
                    fields: [Textarea::make('Reason (optional)', 'note')],
                ),

            ActionButton::make('Update Shipment')
                ->method('updateShipment', params: fn (Order $order) => ['resourceItem' => $order->getKey()])
                ->withConfirm(
                    title: 'Shipment details',
                    fields: fn (mixed $order) => [
                        Text::make('Carrier', 'carrier')->default($order->carrier),
                        Text::make('Tracking number', 'tracking_number')->default($order->tracking_number),
                        Text::make('Tracking URL (https://…)', 'tracking_url')->default($order->tracking_url),
                    ],
                ),

            ActionButton::make('Add Note')
                ->method('addOrderNote', params: fn (Order $order) => ['resourceItem' => $order->getKey()])
                ->withConfirm(
                    title: 'Add internal note',
                    fields: [Textarea::make('Note', 'body')],
                ),
        );
    }

    /**
     * @return list<ComponentContract>
     */
    protected function mainLayer(): array
    {
        return [
            ...parent::mainLayer(),
            FlexibleRender::make(
                fn () => view('admin.orders.detail', ['order' => $this->getResource()->getItem()])
            ),
        ];
    }
}
