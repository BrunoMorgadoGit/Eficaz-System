@props(['messages' => []])

@php
    $errorMessages = is_iterable($messages) ? $messages : [$messages];
@endphp

@if (count($errorMessages))
    <ul {{ $attributes->merge(['class' => 'mt-1.5 space-y-1 text-xs font-medium text-red-600']) }}>
        @foreach ($errorMessages as $message)
            <li>{{ $message }}</li>
        @endforeach
    </ul>
@endif
