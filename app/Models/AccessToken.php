<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AccessToken extends Model
{
    protected $fillable = [
        'user_id',
        'token',
        'last_used',
        'expired_at'
    ];

    protected $hidden = [
        'id',
        'user_id',
        'created_at',
        'updated_at'
    ];

    public function user() {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }
}
