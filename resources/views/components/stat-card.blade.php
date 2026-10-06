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
            <p class="text-xs font-bold uppercase tracking-[0.12em] text-slate-500">{{ $label }}</p>
            <p class="mt-3 text-2xl font-bold tracking-tight text-navy-950">{{ $value }}</p>
        </div>
        <span class="stat-icon {{ $tones[$tone] ?? $tones['cyan'] }}" aria-hidden="true">
            @switch($icon)
                @case('orders')
                    <svg viewBox="0 0 24 24" class="h-5 w-5 fill-none stroke-current" stroke-width="1.8"><path d="M7 4h10a2 2 0 0 1 2 2v15H5V6a2 2 0 0 1 2-2Z"/><path d="M9 4V2h6v2M8.5 10h7M8.5 14h7M8.5 18h4" stroke-linecap="round"/></svg>
                    @break
                @case('revenue')
                    <svg viewBox="0 0 24 24" class="h-5 w-5 fill-none stroke-current" stroke-width="1.8"><circle cx="12" cy="12" r="9"/><path d="M15.5 8.5c-.7-.8-1.8-1.2-3.2-1.2-1.6 0-2.7.8-2.7 2s1 1.8 2.7 2.2 2.8 1 2.8 2.3-1.2 2.2-3 2.2c-1.4 0-2.6-.5-3.5-1.4M12 5.5v13" stroke-linecap="round"/></svg>
                    @break
                @case('products')
                    <svg viewBox="0 0 24 24" class="h-5 w-5 fill-none stroke-current" stroke-width="1.8"><path d="m12 3 8 4.5v9L12 21l-8-4.5v-9L12 3Z" stroke-linejoin="round"/><path d="m4.5 7.8 7.5 4.3 7.5-4.3M12 12v8.5" stroke-linejoin="round"/></svg>
                    @break
                @case('resellers')
                    <svg viewBox="0 0 24 24" class="h-5 w-5 fill-none stroke-current" stroke-width="1.8"><circle cx="9" cy="8" r="3.2"/><path d="M3.5 20v-1.2a5.5 5.5 0 0 1 11 0V20h-11ZM16 5.2a3.2 3.2 0 0 1 0 6.2M17 14a4.8 4.8 0 0 1 3.5 4.6V20H18" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    @break
                @default
                    <svg viewBox="0 0 24 24" class="h-5 w-5 fill-none stroke-current" stroke-width="1.8"><path d="M4 13h6V4H4v9Zm10 7h6V4h-6v16ZM4 20h6v-3H4v3Z" stroke-linejoin="round"/></svg>
            @endswitch
        </span>
    </div>
    @if ($hint)
        <p class="mt-3 text-xs leading-5 text-slate-500">{{ $hint }}</p>
    @endif
</article>
