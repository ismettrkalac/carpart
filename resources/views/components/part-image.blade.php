@props(['part', 'imageClass' => 'size-8 rounded-md object-cover', 'iconClass' => 'size-8 text-neutral-400'])

@if ($part?->primaryImageUrl())
    <img src="{{ $part->primaryImageUrl() }}" alt="{{ $part->name }}" class="{{ $imageClass }} shrink-0">
@else
    <x-category-icon :slug="$part?->category?->slug" :class="$iconClass.' shrink-0'" />
@endif
