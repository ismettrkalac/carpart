@props(['slug' => null, 'class' => 'size-6'])

@php
    $paths = match ($slug) {
        'brakes' => 'M12 3v18M5 7.5h14M5 16.5h14M8 3v4.5M16 3v4.5M8 16.5V21M16 16.5V21',
        'suspension' => 'M4 20 9 9l3 3 3-6 5 14M4 20h16',
        'engine-parts' => 'M4 8h3V5h6v3h3l2 3v6H6v-6l2-3ZM9 8v3h6V8',
        'filters' => 'M4 5h16l-6 8v6l-4 2v-8L4 5Z',
        'ignition' => 'M13 3 4 14h6l-1 7 9-11h-6l1-7Z',
        'cooling-system' => 'M12 3v18M6 6l12 12M18 6 6 18M4 12h16',
        'exhaust' => 'M3 9h9l6-3v12l-6-3H3V9ZM21 10.5v3',
        'electrical' => 'M13 3 4 14h6l-1 7 9-11h-6l1-7Z',
        'body-parts' => 'M3 16 6 7h12l3 9v3H3v-3ZM3 16h18M8 7 6 16M16 7l2 9',
        'transmission' => 'M12 4v3M12 17v3M4 12h3M17 12h3M6.3 6.3l2.1 2.1M15.6 15.6l2.1 2.1M6.3 17.7l2.1-2.1M15.6 8.4l2.1-2.1M12 8a4 4 0 1 0 0 8 4 4 0 0 0 0-8Z',
        default => 'M11 3a4 4 0 0 0-3.8 5.3L3 12.5V17h4.5l4.2-4.2A4 4 0 1 0 11 3Z',
    };
@endphp

<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" {{ $attributes->merge(['class' => $class]) }}>
    <path d="{{ $paths }}"/>
</svg>
