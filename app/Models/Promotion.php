<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Promotion extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'commercial_profile',
        'discount_percentage',
        'starts_on',
        'ends_on',
        'is_active',
        'is_stackable',
    ];

    protected function casts(): array
    {
        return [
            'discount_percentage' => 'integer',
            'starts_on' => 'date',
            'ends_on' => 'date',
            'is_active' => 'boolean',
            'is_stackable' => 'boolean',
        ];
    }

    public function policy(): BelongsTo
    {
        return $this->belongsTo(CommercialPolicy::class, 'commercial_profile', 'commercial_profile');
    }

    public function isAvailableOnToday(): bool
    {
        return $this->is_active
            && $this->starts_on?->startOfDay()->lessThanOrEqualTo(today())
            && $this->ends_on?->endOfDay()->greaterThanOrEqualTo(today());
    }
}
