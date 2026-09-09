<?php

declare(strict_types=1);

namespace App\MoonShine\Resources\Part\Pages;

use App\Enums\PartStatus;
use App\Models\Category;
use App\Models\Manufacturer;
use App\Models\Part;
use App\MoonShine\Resources\Category\CategoryResource;
use App\MoonShine\Resources\Manufacturer\ManufacturerResource;
use App\MoonShine\Resources\Part\PartResource;
use App\MoonShine\Resources\PartImage\PartImageResource;
use Illuminate\Validation\Rule;
use MoonShine\Contracts\Core\TypeCasts\DataWrapperContract;
use MoonShine\Contracts\UI\ComponentContract;
use MoonShine\Contracts\UI\FieldContract;
use MoonShine\Laravel\Fields\Relationships\BelongsTo;
use MoonShine\Laravel\Fields\Relationships\HasMany;
use MoonShine\Laravel\Fields\Slug;
use MoonShine\Laravel\Pages\Crud\FormPage;
use MoonShine\UI\Components\Layout\Box;
use MoonShine\UI\Fields\Enum;
use MoonShine\UI\Fields\ID;
use MoonShine\UI\Fields\Number;
use MoonShine\UI\Fields\Text;
use MoonShine\UI\Fields\Textarea;

/**
 * @extends FormPage<PartResource>
 */
class PartFormPage extends FormPage
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
                    'Manufacturer',
                    'manufacturer',
                    formatted: static fn (Manufacturer $manufacturer) => $manufacturer->name,
                    resource: ManufacturerResource::class,
                )
                    ->nullable()
                    ->valuesQuery(static fn ($query) => $query->select(['id', 'name'])),

                BelongsTo::make(
                    'Category',
                    'category',
                    formatted: static fn (Category $category) => $category->name,
                    resource: CategoryResource::class,
                )
                    ->nullable()
                    ->valuesQuery(static fn ($query) => $query->select(['id', 'name'])),

                Text::make('SKU', 'sku')->required(),

                Text::make('Name', 'name')->required(),

                Slug::make('Slug', 'slug')
                    ->from('name')
                    ->unique()
                    ->required(),

                Textarea::make('Description', 'description')->nullable(),

                Enum::make('Status', 'status')->attach(PartStatus::class)->default(PartStatus::Draft),

                Number::make('Base price (cents)', 'base_price_cents')->required()->min(0),

                Text::make('Currency', 'currency')->default('USD')->required(),

                Number::make('Stock quantity', 'stock_quantity')->required()->min(0),

                Number::make('Weight (kg)', 'weight_kg')->step(0.001)->min(0)->nullable(),
            ]),

            HasMany::make('Images', 'images', resource: PartImageResource::class)
                ->creatable(),
        ];
    }

    protected function rules(DataWrapperContract $item): array
    {
        return [
            'manufacturer_id' => ['nullable', 'exists:manufacturers,id'],
            'category_id' => ['nullable', 'exists:categories,id'],
            'sku' => ['required', 'string', 'max:255', Rule::unique(Part::class)->ignoreModel($item->getOriginal())],
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255', Rule::unique(Part::class)->ignoreModel($item->getOriginal())],
            'description' => ['nullable', 'string'],
            'status' => ['required', Rule::enum(PartStatus::class)],
            'base_price_cents' => ['required', 'integer', 'min:0'],
            'currency' => ['required', 'string', 'size:3'],
            'stock_quantity' => ['required', 'integer', 'min:0'],
            'weight_kg' => ['nullable', 'numeric', 'min:0'],
        ];
    }
}
