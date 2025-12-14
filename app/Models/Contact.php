<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Contact extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'phone',
        'email',
        'address',
        'preferred_platforms',
        'additional_info',
    ];

    protected $casts = [
        'preferred_platforms' => 'json',
    ];

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class, 'contact_id', 'id');
    }
}
