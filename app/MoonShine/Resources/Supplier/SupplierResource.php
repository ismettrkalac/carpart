<?php

declare(strict_types=1);

namespace App\MoonShine\Resources\Supplier;

use App\Models\Supplier;
use App\MoonShine\Resources\Supplier\Pages\SupplierFormPage;
use App\MoonShine\Resources\Supplier\Pages\SupplierIndexPage;
use MoonShine\Laravel\Resources\ModelResource;
use MoonShine\Support\Enums\Action;
use MoonShine\Support\ListOf;

/**
 * @extends ModelResource<Supplier, SupplierIndexPage, SupplierFormPage, null>
 *
 * Supplier.api_config (encrypted credentials used by a future supplier-sync
 * integration) is deliberately not exposed here — see SupplierFormPage.
 */
class SupplierResource extends ModelResource
{
    protected string $model = Supplier::class;

    protected string $title = 'Suppliers';

    protected string $column = 'name';

    /**
     * @return list<class-string>
     */
    protected function pages(): array
    {
        return [
            SupplierIndexPage::class,
            SupplierFormPage::class,
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
