<?php

declare(strict_types=1);

namespace App\MoonShine\Resources\Category\Pages;

use App\Models\Category;
use App\MoonShine\Resources\Category\CategoryResource;
use MoonShine\Contracts\UI\FieldContract;
use MoonShine\Laravel\Pages\Crud\IndexPage;
use MoonShine\UI\Fields\ID;
use MoonShine\UI\Fields\Number;
use MoonShine\UI\Fields\Text;

/**
 * @extends IndexPage<CategoryResource>
 */
class CategoryIndexPage extends IndexPage
{
    /**
     * @return list<FieldContract>
     */
    protected function fields(): iterable
    {
        return [
            ID::make(),
            Text::make('Name', 'name'),
            Text::make('Parent', 'parent', fn (Category $category) => $category->parent?->name ?? '—'),
            Number::make('Position', 'position')->sortable(),
        ];
    }
}
