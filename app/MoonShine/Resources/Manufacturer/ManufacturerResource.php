<?php

declare(strict_types=1);

namespace App\MoonShine\Resources\Manufacturer;

use App\Models\Manufacturer;
use App\MoonShine\Resources\Manufacturer\Pages\ManufacturerFormPage;
use App\MoonShine\Resources\Manufacturer\Pages\ManufacturerIndexPage;
use MoonShine\Laravel\Resources\ModelResource;
use MoonShine\Support\Enums\Action;
use MoonShine\Support\ListOf;

/**
 * @extends ModelResource<Manufacturer, ManufacturerIndexPage, ManufacturerFormPage, null>
 */
class ManufacturerResource extends ModelResource
{
    protected string $model = Manufacturer::class;

    protected string $title = 'Manufacturers';

    protected string $column = 'name';

    /**
     * @return list<class-string>
     */
    protected function pages(): array
    {
        return [
            ManufacturerIndexPage::class,
            ManufacturerFormPage::class,
        ];
    }

    protected function activeActions(): ListOf
    {
        return parent::activeActions()->except(Action::VIEW);
    }

    /**
     * @return list<string>
     */
    protected function search(): array
    {
        return ['name'];
    }
}
