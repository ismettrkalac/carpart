<?php

declare(strict_types=1);

namespace App\MoonShine\Resources\Order\Pages;

use App\Enums\FulfillmentStatus;
use App\Enums\PaymentStatus;
use App\Models\Order;
use App\MoonShine\Resources\Order\OrderResource;
use MoonShine\Contracts\UI\FieldContract;
use MoonShine\Laravel\Pages\Crud\IndexPage;
use MoonShine\UI\Fields\Date;
use MoonShine\UI\Fields\DateRange;
use MoonShine\UI\Fields\Enum;
use MoonShine\UI\Fields\ID;
use MoonShine\UI\Fields\Text;

/**
 * @extends IndexPage<OrderResource>
 */
class OrderIndexPage extends IndexPage
{
    protected bool $isLazy = true;

    /**
     * @return list<FieldContract>
     */
    protected function fields(): iterable
    {
        return [
            ID::make(),
            Text::make('Order #', 'uuid', fn (Order $order) => $order->orderNumber()),
            Text::make('Customer', 'email'),
            Text::make('Total', 'total_cents', fn (Order $order) => $order->formattedTotal()),
            Text::make('Currency', 'currency'),
            Enum::make('Payment', 'payment_status')->attach(PaymentStatus::class),
            Enum::make('Fulfillment', 'fulfillment_status')->attach(FulfillmentStatus::class),
            Date::make('Placed', 'created_at')->format('M j, Y g:i A')->sortable(),
        ];
    }

    /**
     * @return list<FieldContract>
     */
    protected function filters(): iterable
    {
        return [
            Enum::make('Payment status', 'payment_status')->attach(PaymentStatus::class),
            Enum::make('Fulfillment status', 'fulfillment_status')->attach(FulfillmentStatus::class),
            DateRange::make('Placed', 'created_at'),
        ];
    }
}
