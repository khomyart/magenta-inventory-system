<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Move extends Model
{
    public $table = "move";

    protected $fillable = [
        "item_id",
        "from_warehouse_id",
        "to_warehouse_id",
        "amount", "reason_name",
        "additional_reason_name",
        "detail"];
    protected $hidden = ["created_at", "updated_at"];
}
