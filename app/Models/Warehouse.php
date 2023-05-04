<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Warehouse extends Model
{
    use HasFactory;

    protected $fillable = ['country_id', 'city_id', 'address', 'name', 'description'];
    protected $hidden = ['created_at', 'updated_at'];

    public function country() {
        return $this->belongsTo(Country::class, 'country_id', 'id');
    }

    public function city() {
        return $this->belongsTo(City::class, 'city_id', 'id');
    }

    public function items() {
        return ItemWarehouseAmount::where("warehouse_id", $this->id)->get();
    }
}
