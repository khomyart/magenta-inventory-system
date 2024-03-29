<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Income extends Model
{
    use HasFactory;

    public $table = "income";

    protected $fillable = ["item_id", "warehouse_id", "amount_of_items", "price_per_item", "currency"];
    protected $hidden = ["created_at", "updated_at"];
}
