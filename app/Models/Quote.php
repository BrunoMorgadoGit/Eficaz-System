<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Quote extends Model
{
    use HasFactory;

    protected $fillable = [
        'reseller_id',
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

    public function items(): HasMany
    {
        return $this->hasMany(QuoteItem::class);
    }

    public function order(): HasOne
    {
        return $this->hasOne(Order::class);
    }

    public function promotion(): BelongsTo
    {
        return $this->belongsTo(Promotion::class);
    }

    public function reference(): string
    {
        $year = $this->created_at?->format('Y') ?? '—';
        $id = str_pad((string) $this->getKey(), 4, '0', STR_PAD_LEFT);

        return "ORC-{$year}-{$id}";
    }
}
