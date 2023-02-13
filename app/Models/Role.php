<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Role extends Model
{
    use HasFactory;

    public function allowenses() {
        return $this->belongsToMany(Allowense::class, 'roles_allowenses', 'role_id', 'allowense_id');
    }
}
