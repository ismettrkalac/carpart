<?php

declare(strict_types=1);

namespace App\MoonShine\Resources\Supplier\Pages;

use App\MoonShine\Resources\Supplier\SupplierResource;
use MoonShine\Contracts\UI\FieldContract;
use MoonShine\Laravel\Pages\Crud\IndexPage;
use MoonShine\UI\Fields\Date;
use MoonShine\UI\Fields\ID;
use MoonShine\UI\Fields\Switcher;
use MoonShine\UI\Fields\Text;

/**
 * @extends IndexPage<SupplierResource>
 */
class SupplierIndexPage extends IndexPage
{
    /**
     * @return list<FieldContract>
     */
    protected function fields(): iterable
    {
        return [
            ID::make(),
            Text::make('Name', 'name'),
            Text::make('API driver', 'api_driver'),
            Switcher::make('Active', 'is_active'),
            Date::make('Last synced', 'last_synced_at')->format('M j, Y g:i A')->sortable(),
        ];
    }
}
