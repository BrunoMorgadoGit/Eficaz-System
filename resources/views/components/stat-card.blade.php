@props([
    'label',
    'value',
    'hint' => null,
    'tone' => 'cyan',
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
            <p class="text-xs font-bold uppercase tracking-[0.12em] text-slate-500">{{ $label }}</p>
            <p class="mt-3 text-2xl font-bold tracking-tight text-navy-950">{{ $value }}</p>
        </div>
        <span class="stat-icon {{ $tones[$tone] ?? $tones['cyan'] }}" aria-hidden="true">
            <svg viewBox="0 0 24 24" class="h-5 w-5 fill-none stroke-current" stroke-width="1.8"><path d="M4 19V9l8-5 8 5v10M8 19v-6h8v6M4 9h16" stroke-linejoin="round"/></svg>
        </span>
    </div>
    @if ($hint)
        <p class="mt-3 text-xs leading-5 text-slate-500">{{ $hint }}</p>
    @endif
</article>
