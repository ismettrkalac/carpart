<?php

declare(strict_types=1);

namespace App\MoonShine\Resources\Part\Pages;

use App\Enums\PartStatus;
use App\Models\Part;
use App\MoonShine\Resources\Part\PartResource;
use MoonShine\Contracts\UI\FieldContract;
use MoonShine\Laravel\Pages\Crud\IndexPage;
use MoonShine\UI\Fields\Enum;
use MoonShine\UI\Fields\ID;
use MoonShine\UI\Fields\Number;
use MoonShine\UI\Fields\Text;

/**
 * @extends IndexPage<PartResource>
 */
class PartIndexPage extends IndexPage
{
    protected bool $isLazy = true;

    /**
     * @return list<FieldContract>
     */
    protected function fields(): iterable
    {
        return [
            ID::make(),
            Text::make('SKU', 'sku'),
            Text::make('Name', 'name'),
            Text::make('Manufacturer', 'manufacturer', fn (Part $part) => $part->manufacturer?->name ?? '—'),
            Text::make('Category', 'category', fn (Part $part) => $part->category?->name ?? '—'),
            Enum::make('Status', 'status')->attach(PartStatus::class),
            Text::make('Price', 'base_price_cents', fn (Part $part) => $part->formattedPrice()),
            Number::make('Stock', 'stock_quantity')->sortable(),
        ];
    }

    /**
     * @return list<FieldContract>
     */
    protected function filters(): iterable
    {
        return [
            Enum::make('Status', 'status')->attach(PartStatus::class),
        ];
    }
}
