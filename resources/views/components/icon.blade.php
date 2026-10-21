{{-- Tabler icon from public/icons/tabler/sprite.svg. Usage: <x-icon name="package" size="1em" stroke-width="2" /> --}}
@props(['name', 'size' => 24])

<svg
    xmlns="http://www.w3.org/2000/svg"
    viewBox="0 0 24 24"
    fill="none"
    stroke="currentColor"
    stroke-linecap="round"
    stroke-linejoin="round"
    {{ $attributes->merge(['class' => 'icon', 'width' => $size, 'height' => $size, 'stroke-width' => 1.8, 'aria-hidden' => 'true', 'focusable' => 'false']) }}
>
    <use href="{{ asset('icons/tabler/sprite.svg') }}#{{ $name }}" />
</svg>
