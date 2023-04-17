<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Item extends Model
{
    use HasFactory;

    protected $fillable = [
        'article', 'title', 'type_id',
        'gender_id', 'size_id', 'color_id',
        'unit_id', 'price', 'currency'
    ];

    protected $hidden = [
        "created_at", "updated_at"
    ];

    public function type() {
        return $this->hasOne(Type::class, 'id', 'type_id');
    }

    public function images() {
        return $this->hasMany(Image::class, 'item_id', 'id');
    }
}
