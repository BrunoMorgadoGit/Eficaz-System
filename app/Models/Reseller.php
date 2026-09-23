<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Reseller extends Model
{
    use HasFactory;

    public const PROFILE_STANDARD = 'STANDARD';

    public const PROFILE_GOLD = 'GOLD';

    public const PROFILE_PREMIUM = 'PREMIUM';

    protected $fillable = [
        'user_id',
        'company_name',
        'commercial_profile',
        'credit_limit',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'credit_limit' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function cart(): HasOne
    {
        return $this->hasOne(Cart::class);
    }

    public function quotes(): HasMany
    {
        return $this->hasMany(Quote::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function discountPercentage(): int
    {
        return match ($this->commercial_profile) {
            self::PROFILE_GOLD => 5,
            self::PROFILE_PREMIUM => 10,
            default => 0,
        };
    }
}
