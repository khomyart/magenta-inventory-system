<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Color extends Model
{
    use HasFactory;

    protected $fillable = ['value','article','description','text_color_value'];
    protected $hidden = ['created_at', 'updated_at'];
}
