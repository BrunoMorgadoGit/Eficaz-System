<x-layouts.app title="Meus Orçamentos | Eficaz B2B" active-nav="quotes">
    <section class="dashboard-list-panel" aria-labelledby="quote-history-title">
        <header class="dashboard-list-heading">
            <h2 id="quote-history-title">Histórico de Orçamentos</h2>
            <a href="{{ route('reseller.products.index') }}">
                Novo orçamento <span aria-hidden="true"><x-icon name="arrow-right" size="1em" /></span>
            </a>
        </header>

        @forelse ($quotes as $quote)
            <a href="{{ route('reseller.quotes.show', $quote) }}" class="dashboard-list-row">
                <span class="dashboard-row-description">
                    <strong>{{ $quote->reference() }}</strong>
                    <span>
                        {{ $quote->created_at?->format('d/m/Y H:i') ?? '—' }}
                        · {{ $quote->items_count }} {{ $quote->items_count === 1 ? 'item' : 'itens' }}
                    </span>
                </span>
                <strong class="dashboard-row-total">R$ {{ number_format((float) $quote->total, 2, ',', '.') }}</strong>
                <span @class([
                    'dashboard-quote-status',
                    'dashboard-quote-status--converted' => $quote->order,
                    'dashboard-quote-status--open' => ! $quote->order,
                ])>
                    <span aria-hidden="true"></span>{{ $quote->order ? 'Convertido' : 'Em aberto' }}
                </span>
            </a>
        @empty
            <div class="dashboard-list-empty">
                <p>Nenhum orçamento registrado ainda.</p>
                <a href="{{ route('reseller.products.index') }}">Buscar produtos por SKU</a>
            </div>
        @endforelse

        {{ $quotes->links() }}
    </section>
</x-layouts.app>
