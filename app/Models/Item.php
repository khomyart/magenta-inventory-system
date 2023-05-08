<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Item extends Model
{
    use HasFactory;

    protected $fillable = [
        "group_id", "article", "title", "model", "type_id",
        "gender_id", "size_id", "color_id",
        "unit_id", "price", "lack", "currency"
    ];

    protected $hidden = [
        "created_at", "updated_at"
    ];

    public function type() {
        return $this->hasOne(Type::class, "id", "type_id");
    }

    public function gender() {
        return $this->hasOne(Gender::class, "id", "gender_id");
    }

    public function size() {
        return $this->hasOne(Size::class, "id", "size_id");
    }

    public function color() {
        return $this->hasOne(Color::class, "id", "color_id");
    }

    public function unit() {
        return $this->hasOne(Unit::class, "id", "unit_id");
    }

    public function images() {
        return $this->hasMany(Image::class, "item_id", "id");
    }
}
