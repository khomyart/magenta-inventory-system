<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class City extends Model
{
    use HasFactory;

    protected $fillable = ['country_id', 'name'];
    protected $hidden = ['created_at', 'updated_at'];

    public function country() {
        return $this->belongsTo(Country::class, 'country_id', 'id');
    }

    public function warehouses() {
        return $this->hasMany(Warehouse::class, 'city_id', 'id');
    }
}
