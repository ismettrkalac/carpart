<?php

declare(strict_types=1);

namespace App\MoonShine\Resources\PartImage;

use App\Models\PartImage;
use App\MoonShine\Resources\PartImage\Pages\PartImageFormPage;
use App\MoonShine\Resources\PartImage\Pages\PartImageIndexPage;
use MoonShine\Laravel\Resources\ModelResource;

/**
 * @extends ModelResource<PartImage, PartImageIndexPage, PartImageFormPage, null>
 *
 * Not added to the admin menu — managed only through the "Images" field on
 * PartFormPage, one part's gallery at a time.
 */
class PartImageResource extends ModelResource
{
    protected string $model = PartImage::class;

    protected string $title = 'Part Images';

    protected string $sortColumn = 'position';

    /**
     * @return list<class-string>
     */
    protected function pages(): array
    {
        return [
            PartImageIndexPage::class,
            PartImageFormPage::class,
        ];
    }
}
