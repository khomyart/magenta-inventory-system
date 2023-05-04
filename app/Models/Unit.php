<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Unit extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'description'];
    protected $hidden = ['created_at', 'updated_at'];

    public function items() {
        return $this->belongsTo(Item::class, "id", "unit_id");
    }
}
