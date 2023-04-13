<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Image extends Model
{
    use HasFactory;

    protected $fillable = ["item_id", "src", "number_in_row"];
    protected $hidden = ["created_at", "updated_at"];
}
