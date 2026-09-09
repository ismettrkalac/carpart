<?php

declare(strict_types=1);

namespace App\MoonShine\Resources\PartImage\Pages;

use App\MoonShine\Resources\PartImage\PartImageResource;
use MoonShine\Contracts\Core\TypeCasts\DataWrapperContract;
use MoonShine\Contracts\UI\ComponentContract;
use MoonShine\Contracts\UI\FieldContract;
use MoonShine\Laravel\Pages\Crud\FormPage;
use MoonShine\UI\Components\Layout\Box;
use MoonShine\UI\Fields\Hidden;
use MoonShine\UI\Fields\ID;
use MoonShine\UI\Fields\Image;
use MoonShine\UI\Fields\Number;

/**
 * @extends FormPage<PartImageResource>
 */
class PartImageFormPage extends FormPage
{
    /**
     * @return list<ComponentContract|FieldContract>
     */
    protected function fields(): iterable
    {
        return [
            Box::make([
                ID::make(),

                Image::make('Image', 'path')
                    ->disk(moonshineConfig()->getDisk())
                    ->dir('parts')
                    ->allowedExtensions(['jpg', 'jpeg', 'png', 'webp']),

                Number::make('Position', 'position')->default(0)->min(0),
            ]),

            // Set by the HasMany field on PartFormPage (a hidden input
            // carrying the parent part's id) — never shown or editable
            // directly, since this resource only ever exists nested under
            // a Part's "Images" field.
            Hidden::make('part_id'),
        ];
    }

    protected function rules(DataWrapperContract $item): array
    {
        return [
            'part_id' => ['required', 'integer', 'exists:parts,id'],
            'path' => [
                ...$item->getKey() !== null ? ['sometimes', 'nullable'] : ['required'],
                'image', 'mimes:jpg,jpeg,png,webp', 'max:5120',
            ],
            'position' => ['nullable', 'integer', 'min:0'],
        ];
    }
}
