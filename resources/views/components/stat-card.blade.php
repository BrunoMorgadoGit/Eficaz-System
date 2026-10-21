@props([
    'label',
    'value',
    'hint' => null,
    'tone' => 'cyan',
    'icon' => 'overview',
])

@php
    $tones = [
        'cyan' => 'bg-cyan-50 text-cyan-700',
        'navy' => 'bg-navy-900 text-cyan-400',
        'green' => 'bg-emerald-50 text-emerald-700',
        'amber' => 'bg-amber-50 text-amber-700',
    ];
@endphp

<article {{ $attributes->merge(['class' => 'stat-card']) }}>
    <div class="flex items-start justify-between gap-4">
        <div>
            <p class="text-xs font-heading font-semibold uppercase tracking-[0.12em] text-slate-500">{{ $label }}</p>
            <p class="mt-3 text-2xl font-heading font-bold tracking-tight text-navy-950">{{ $value }}</p>
        </div>
        <span class="stat-icon {{ $tones[$tone] ?? $tones['cyan'] }}" aria-hidden="true">
            @switch($icon)
                @case('orders')
                    <x-icon name="clipboard-list" class="h-5 w-5 fill-none stroke-current" stroke-width="1.8" />
                    @break
                @case('revenue')
                    <x-icon name="coin" class="h-5 w-5 fill-none stroke-current" stroke-width="1.8" />
                    @break
                @case('products')
                    <x-icon name="package" class="h-5 w-5 fill-none stroke-current" stroke-width="1.8" />
                    @break
                @case('resellers')
                    <x-icon name="users" class="h-5 w-5 fill-none stroke-current" stroke-width="1.8" />
                    @break
                @default
                    <x-icon name="layout-dashboard" class="h-5 w-5 fill-none stroke-current" stroke-width="1.8" />
            @endswitch
        </span>
    </div>
    @if ($hint)
        <p class="mt-3 text-xs leading-5 text-slate-500">{{ $hint }}</p>
    @endif
</article>
