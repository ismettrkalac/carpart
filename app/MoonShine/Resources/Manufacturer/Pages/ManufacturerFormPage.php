<?php

declare(strict_types=1);

namespace App\MoonShine\Resources\Manufacturer\Pages;

use App\Models\Manufacturer;
use App\MoonShine\Resources\Manufacturer\ManufacturerResource;
use Illuminate\Validation\Rule;
use MoonShine\Contracts\Core\TypeCasts\DataWrapperContract;
use MoonShine\Contracts\UI\ComponentContract;
use MoonShine\Contracts\UI\FieldContract;
use MoonShine\Laravel\Fields\Slug;
use MoonShine\Laravel\Pages\Crud\FormPage;
use MoonShine\UI\Components\Layout\Box;
use MoonShine\UI\Fields\ID;
use MoonShine\UI\Fields\Text;
use MoonShine\UI\Fields\Url;

/**
 * @extends FormPage<ManufacturerResource>
 */
class ManufacturerFormPage extends FormPage
{
    /**
     * @return list<ComponentContract|FieldContract>
     */
    protected function fields(): iterable
    {
        return [
            Box::make([
                ID::make(),

                Text::make('Name', 'name')->required(),

                Slug::make('Slug', 'slug')
                    ->from('name')
                    ->unique()
                    ->required(),

                Url::make('Logo URL', 'logo_path')->nullable(),
            ]),
        ];
    }

    protected function rules(DataWrapperContract $item): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255', Rule::unique(Manufacturer::class)->ignoreModel($item->getOriginal())],
            'logo_path' => ['nullable', 'string', 'max:2048', 'url'],
        ];
    }
}
