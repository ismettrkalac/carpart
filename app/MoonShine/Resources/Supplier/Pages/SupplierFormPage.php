<?php

declare(strict_types=1);

namespace App\MoonShine\Resources\Supplier\Pages;

use App\Models\Supplier;
use App\MoonShine\Resources\Supplier\SupplierResource;
use Illuminate\Validation\Rule;
use MoonShine\Contracts\Core\TypeCasts\DataWrapperContract;
use MoonShine\Contracts\UI\ComponentContract;
use MoonShine\Contracts\UI\FieldContract;
use MoonShine\Laravel\Fields\Slug;
use MoonShine\Laravel\Pages\Crud\FormPage;
use MoonShine\UI\Components\Layout\Box;
use MoonShine\UI\Fields\ID;
use MoonShine\UI\Fields\Switcher;
use MoonShine\UI\Fields\Text;

/**
 * @extends FormPage<SupplierResource>
 *
 * api_config (encrypted API credentials) is intentionally not editable here
 * — it's read/written only by the future supplier-sync integration that
 * will consume it; exposing raw credentials in this admin form is out of
 * scope until that integration (and a proper masked/rotate-credential UX)
 * exists.
 */
class SupplierFormPage extends FormPage
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

                Text::make('API driver', 'api_driver')->nullable(),

                Switcher::make('Active', 'is_active')->default(true),
            ]),
        ];
    }

    protected function rules(DataWrapperContract $item): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255', Rule::unique(Supplier::class)->ignoreModel($item->getOriginal())],
            'api_driver' => ['nullable', 'string', 'max:255'],
            'is_active' => ['boolean'],
        ];
    }
}
