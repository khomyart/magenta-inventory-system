<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Income extends Model
{
    use HasFactory;

    public $table = 'income';

    protected $fillable = ['item_id', 'warehouse_id', 'amount_of_items', 'price_per_item', 'currency', 'order_id'];

    protected $hidden = ['created_at', 'updated_at'];

    /**
     * Зв'язок з замовленням
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * Зв'язок з товаром
     */
    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }

    /**
     * Зв'язок зі складом
     */
    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }
}
