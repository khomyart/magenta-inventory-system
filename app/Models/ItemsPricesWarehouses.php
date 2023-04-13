<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ItemsPricesWarehouses extends Model
{
    use HasFactory;

    protected $table = 'items_prices_warehouses';

    protected $fillable = ["item_id", "warehouse_id", "amount_of_items", "price_per_item", "currency"];
    protected $hidden = ["created_at", "updated_at"];
}
