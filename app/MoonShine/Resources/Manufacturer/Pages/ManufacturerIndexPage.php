<?php

declare(strict_types=1);

namespace App\MoonShine\Resources\Manufacturer\Pages;

use App\MoonShine\Resources\Manufacturer\ManufacturerResource;
use MoonShine\Contracts\UI\FieldContract;
use MoonShine\Laravel\Pages\Crud\IndexPage;
use MoonShine\UI\Fields\ID;
use MoonShine\UI\Fields\Text;

/**
 * @extends IndexPage<ManufacturerResource>
 */
class ManufacturerIndexPage extends IndexPage
{
    /**
     * @return list<FieldContract>
     */
    protected function fields(): iterable
    {
        return [
            ID::make(),
            Text::make('Name', 'name'),
            Text::make('Slug', 'slug'),
        ];
    }
}
