<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockMovement extends Model
{
    use HasFactory;

    public const TYPE_BASELINE = 'BASELINE';

    public const TYPE_ADJUSTMENT = 'ADJUSTMENT';

    public const TYPE_ORDER_ISSUE = 'ORDER_ISSUE';

    protected $fillable = [
        'product_id',
        'sku',
        'movement_type',
        'quantity_change',
        'stock_before',
        'stock_after',
        'order_id',
        'performed_by',
    ];

    protected function casts(): array
    {
        return [
            'quantity_change' => 'integer',
            'stock_before' => 'integer',
            'stock_after' => 'integer',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function performer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'performed_by');
    }
}
