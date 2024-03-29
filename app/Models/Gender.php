<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Gender extends Model
{
    use HasFactory;

    protected $fillable = ["name", "number_in_row"];
    protected $hidden = ["created_at", "updated_at"];

    public function items() {
        return $this->belongsTo(Item::class, "id", "gender_id");
    }
}
