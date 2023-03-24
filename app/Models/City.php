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
        $this->belongsTo(Country::class, 'country_id', 'id');
    }
}
