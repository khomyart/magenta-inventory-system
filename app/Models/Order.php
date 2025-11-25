<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Order extends Model
{
    use HasFactory;

    protected $fillable = [
        'status',
        'amount_of_advance_payment_on_card',
        'amount_of_advance_payment_via_terminal',
        'amount_of_advance_payment_as_cash',
        'amount_of_final_payment_on_card',
        'amount_of_final_payment_via_terminal',
        'amount_of_final_payment_as_cash',
        'currency',
        'discount',
        'total_price',
        'completion_deadline',
        'fully_payed_at',
        'completed_at',
        'contact_id',
        'warehouse_id',
        'notes',
        'involvement_level_1_user_id',
        'involvement_level_2_user_id',
        'involvement_level_3_user_id',
    ];

    protected $casts = [
        'completion_deadline' => 'datetime',
        'fully_payed_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class, 'contact_id', 'id');
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'warehouse_id', 'id');
    }

    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class, 'order_id', 'id');
    }

    public function orderServices(): HasMany
    {
        return $this->hasMany(OrderService::class, 'order_id', 'id');
    }

    public function outcomes(): HasMany
    {
        return $this->hasMany(Outcome::class, 'order_id', 'id');
    }

    public function incomes(): HasMany
    {
        return $this->hasMany(Income::class, 'order_id', 'id');
    }

    public function statusHistory(): HasMany
    {
        return $this->hasMany(OrderStatusHistory::class, 'order_id', 'id');
    }

    public function involvementLevel1User(): BelongsTo
    {
        return $this->belongsTo(User::class, 'involvement_level_1_user_id', 'id');
    }

    public function involvementLevel2User(): BelongsTo
    {
        return $this->belongsTo(User::class, 'involvement_level_2_user_id', 'id');
    }

    public function involvementLevel3User(): BelongsTo
    {
        return $this->belongsTo(User::class, 'involvement_level_3_user_id', 'id');
    }
}
