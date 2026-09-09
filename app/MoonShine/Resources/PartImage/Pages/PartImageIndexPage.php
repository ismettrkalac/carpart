<?php

declare(strict_types=1);

namespace App\MoonShine\Resources\PartImage\Pages;

use App\MoonShine\Resources\PartImage\PartImageResource;
use MoonShine\Contracts\UI\FieldContract;
use MoonShine\Laravel\Pages\Crud\IndexPage;
use MoonShine\UI\Fields\ID;
use MoonShine\UI\Fields\Image;
use MoonShine\UI\Fields\Number;

/**
 * @extends IndexPage<PartImageResource>
 */
class PartImageIndexPage extends IndexPage
{
    /**
     * @return list<FieldContract>
     */
    protected function fields(): iterable
    {
        return [
            ID::make(),
            Image::make('Image', 'path')->disk(moonshineConfig()->getDisk()),
            Number::make('Position', 'position')->sortable(),
        ];
    }
}
