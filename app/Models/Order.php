<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Order extends Model
{
    use HasFactory;

    public const STATUS_PENDENTE = 'PENDENTE';

    public const STATUS_APROVADO = 'APROVADO';

    public const STATUS_CONCLUIDO = 'CONCLUIDO';

    protected $fillable = [
        'reseller_id',
        'quote_id',
        'status',
        'subtotal',
        'discount',
        'total',
        'commercial_profile',
        'profile_discount_percentage',
        'promotion_id',
        'promotion_name',
        'promotion_discount_percentage',
        'promotion_is_stackable',
        'payment_terms',
    ];

    protected function casts(): array
    {
        return [
            'subtotal' => 'decimal:2',
            'discount' => 'decimal:2',
            'total' => 'decimal:2',
            'profile_discount_percentage' => 'integer',
            'promotion_discount_percentage' => 'integer',
            'promotion_is_stackable' => 'boolean',
        ];
    }

    public function reseller(): BelongsTo
    {
        return $this->belongsTo(Reseller::class);
    }

    public function quote(): BelongsTo
    {
        return $this->belongsTo(Quote::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function promotion(): BelongsTo
    {
        return $this->belongsTo(Promotion::class);
    }
}
