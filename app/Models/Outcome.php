<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Outcome extends Model
{
    use HasFactory;

    public $table = "outcome";

    protected $fillable = ["item_id", "warehouse_id", "amount", "reason_name", "additional_reason_name", "detail"];
    protected $hidden = ["created_at", "updated_at"];
}
