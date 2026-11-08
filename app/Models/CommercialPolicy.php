<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CommercialPolicy extends Model
{
    use HasFactory;

    protected $fillable = [
        'commercial_profile',
        'discount_percentage',
        'payment_terms',
    ];

    protected function casts(): array
    {
        return ['discount_percentage' => 'integer'];
    }

    public function promotions(): HasMany
    {
        return $this->hasMany(Promotion::class, 'commercial_profile', 'commercial_profile');
    }
}
