<?php

declare(strict_types=1);

namespace App\MoonShine\Resources\Part;

use App\Models\Part;
use App\MoonShine\Resources\Part\Pages\PartFormPage;
use App\MoonShine\Resources\Part\Pages\PartIndexPage;
use MoonShine\Laravel\Resources\ModelResource;
use MoonShine\Support\Enums\Action;
use MoonShine\Support\ListOf;

/**
 * @extends ModelResource<Part, PartIndexPage, PartFormPage, null>
 */
class PartResource extends ModelResource
{
    protected string $model = Part::class;

    protected string $title = 'Parts';

    protected string $column = 'name';

    protected string $sortColumn = 'created_at';

    protected array $with = ['manufacturer', 'category'];

    /**
     * @return list<class-string>
     */
    protected function pages(): array
    {
        return [
            PartIndexPage::class,
            PartFormPage::class,
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
        return ['sku', 'name', 'description'];
    }
}
