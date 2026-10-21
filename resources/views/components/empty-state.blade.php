@props([
    'title' => 'Ainda não há informações',
    'description' => 'Quando houver movimentações, elas aparecerão aqui.',
    'actionLabel' => null,
    'actionRoute' => null,
])

<div {{ $attributes->merge(['class' => 'empty-state']) }}>
    <span class="empty-state-icon" aria-hidden="true">
        <x-icon name="inbox" class="h-6 w-6 fill-none stroke-current" stroke-width="1.8" />
    </span>
    <h3 class="mt-4 text-sm font-bold text-navy-950">{{ $title }}</h3>
    <p class="mt-1 max-w-sm text-sm leading-6 text-slate-500">{{ $description }}</p>
    @if ($actionLabel && $actionRoute)
        <a href="{{ $actionRoute }}" class="btn btn-secondary mt-5">{{ $actionLabel }}</a>
    @endif
</div>
