<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BusinessAccountTransaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'total_price',
        'amount_on_card',
        'amount_via_terminal',
        'amount_as_cash',
        'currency',
        'happened_at',
        'created_by_user_id',
        'type',
    ];

    protected $casts = [
        'happened_at' => 'datetime',
        'total_price' => 'decimal:4',
        'amount_on_card' => 'decimal:4',
        'amount_via_terminal' => 'decimal:4',
        'amount_as_cash' => 'decimal:4',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }
}
