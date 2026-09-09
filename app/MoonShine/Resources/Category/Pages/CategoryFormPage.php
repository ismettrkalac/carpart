<?php

declare(strict_types=1);

namespace App\MoonShine\Resources\Category\Pages;

use App\Models\Category;
use App\MoonShine\Resources\Category\CategoryResource;
use Illuminate\Validation\Rule;
use MoonShine\Contracts\Core\TypeCasts\DataWrapperContract;
use MoonShine\Contracts\UI\ComponentContract;
use MoonShine\Contracts\UI\FieldContract;
use MoonShine\Laravel\Fields\Relationships\BelongsTo;
use MoonShine\Laravel\Fields\Slug;
use MoonShine\Laravel\Pages\Crud\FormPage;
use MoonShine\UI\Components\Layout\Box;
use MoonShine\UI\Fields\ID;
use MoonShine\UI\Fields\Number;
use MoonShine\UI\Fields\Text;

/**
 * @extends FormPage<CategoryResource>
 */
class CategoryFormPage extends FormPage
{
    /**
     * @return list<ComponentContract|FieldContract>
     */
    protected function fields(): iterable
    {
        return [
            Box::make([
                ID::make(),

                BelongsTo::make(
                    'Parent category',
                    'parent',
                    formatted: static fn (Category $category) => $category->name,
                    resource: CategoryResource::class,
                )
                    ->nullable()
                    ->valuesQuery(static fn ($query) => $query->select(['id', 'name'])),

                Text::make('Name', 'name')->required(),

                Slug::make('Slug', 'slug')
                    ->from('name')
                    ->unique()
                    ->required(),

                Number::make('Position', 'position')->default(0)->min(0),
            ]),
        ];
    }

    protected function rules(DataWrapperContract $item): array
    {
        $categoryId = $item->getOriginal()->getKey();

        return [
            'parent_id' => ['nullable', 'exists:categories,id', Rule::notIn(array_filter([$categoryId]))],
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255', Rule::unique(Category::class)->ignoreModel($item->getOriginal())],
            'position' => ['nullable', 'integer', 'min:0'],
        ];
    }
}
