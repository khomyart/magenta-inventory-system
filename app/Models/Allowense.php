<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Allowense extends Model
{
    use HasFactory;

    protected $fillable = [
        'section', 'action',
    ];

    public $timestamps = false;

    public function roles()
    {
        return $this->belongsToMany(Role::class, 'roles_allowenses', 'allowense_id', 'role_id');
    }
}
