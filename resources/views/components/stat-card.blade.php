@props([
    'label',
    'value',
    'hint' => null,
    'tone' => 'brand',
    'icon' => 'layout-dashboard',
])

@php
    $tones = [
        'brand' => 'bg-brand-50 text-brand-700',
        'navy' => 'bg-navy-900 text-brand-400',
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
        <span class="stat-icon {{ $tones[$tone] ?? $tones['brand'] }}" aria-hidden="true">
            <x-icon :name="$icon" class="h-5 w-5" />
        </span>
    </div>
    @if ($hint)
        <p class="mt-3 text-xs leading-5 text-slate-500">{{ $hint }}</p>
    @endif
</article>
