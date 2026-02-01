<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Spend extends Model
{
    use HasFactory;

    protected $fillable = [
        'title', 'total_price', 'amount_on_card', 'amount_via_terminal', 'amount_as_cash', 'currency', 'happened_at', 'is_hidden', 'created_by_user_id',
    ];

    protected $casts = [
        'happened_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id', 'id');
    }
}
