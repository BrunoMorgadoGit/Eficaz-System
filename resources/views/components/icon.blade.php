@props(['name'])

<svg
    xmlns="http://www.w3.org/2000/svg"
    viewBox="0 0 24 24"
    fill="none"
    stroke="currentColor"
    stroke-linecap="round"
    stroke-linejoin="round"
    aria-hidden="true"
    focusable="false"
    {{ $attributes->merge(['class' => 'icon icon-tabler icon-tabler-' . $name, 'width' => 24, 'height' => 24, 'stroke-width' => '1.8']) }}
>
    <use href="{{ asset('icons/tabler/sprite.svg') }}#{{ $name }}" />
</svg>
