<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Contact extends Model
{
    use HasFactory;

    protected $fillable = [
        "name",
        "phone",
        "email",
        "address",
        "preferred_platforms",
        "additional_info"
    ];

    protected $casts = [
        "preferred_platforms" => "json"
    ];
}
