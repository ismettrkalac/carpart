<?php

namespace Tests\Unit;

use App\Models\Part;
use App\Models\PartImage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PartImagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_images_are_ordered_by_position(): void
    {
        $part = Part::factory()->create();
        $second = PartImage::factory()->for($part)->create(['position' => 2]);
        $first = PartImage::factory()->for($part)->create(['position' => 1]);

        $this->assertSame([$first->id, $second->id], $part->images->pluck('id')->all());
    }

    public function test_primary_image_url_returns_the_first_images_url(): void
    {
        $part = Part::factory()->create();
        PartImage::factory()->for($part)->create(['position' => 1, 'path' => 'parts/second.jpg']);
        PartImage::factory()->for($part)->create(['position' => 0, 'path' => 'parts/first.jpg']);

        $this->assertStringContainsString('parts/first.jpg', $part->primaryImageUrl());
    }

    public function test_primary_image_url_is_null_when_the_part_has_no_images(): void
    {
        $part = Part::factory()->create();

        $this->assertNull($part->primaryImageUrl());
    }

    public function test_deleting_a_part_removes_its_images(): void
    {
        $part = Part::factory()->create();
        $image = PartImage::factory()->for($part)->create();

        $part->delete();

        $this->assertModelMissing($image);
    }
}
