@props(['status' => 'pending', 'label' => null])

@php
    $normalized = strtolower(str_replace([' ', '_'], '-', (string) $status));
    $labels = [
        'pending' => 'Pendente',
        'pendente' => 'Pendente',
        'processing' => 'Em processamento',
        'approved' => 'Aprovado',
        'aprovado' => 'Aprovado',
        'quoted' => 'Orçado',
        'sent' => 'Enviado',
        'confirmed' => 'Confirmado',
        'paid' => 'Pago',
        'completed' => 'Concluído',
        'concluido' => 'Concluído',
        'cancelled' => 'Cancelado',
        'rejected' => 'Recusado',
        'active' => 'Ativo',
        'inactive' => 'Inativo',
    ];
    $text = $label ?? $labels[$normalized] ?? ucfirst(str_replace('-', ' ', $normalized));
@endphp

<span {{ $attributes->merge(['class' => 'status-badge status-' . $normalized]) }}>
    <span class="status-dot" aria-hidden="true"></span>
    {{ $text }}
</span>
