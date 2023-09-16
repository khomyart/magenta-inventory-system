<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UsersFavoriteWarehouses extends Model
{
    public $table = "users_favorite_warehouses";

    protected $fillable = ["user_id", "warehouse_id"];
    protected $hidden = ["created_at", "updated_at"];
}
