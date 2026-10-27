<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ItemReturnDetail extends Model
{
    public const GOOD = 'GOOD';
    public const DAMAGED = 'DAMAGED';
    public const INCOMPLETE = 'INCOMPLETE';

    public const CONDITIONS = [
        self::GOOD => 'Bueno',
        self::DAMAGED => 'Dañado',
        self::INCOMPLETE => 'Incompleto',
    ];

    public $timestamps = false;

    protected $fillable = ['item_return_id', 'delivery_detail_id', 'item_id', 'quantity', 'condition', 'notes'];

    protected $casts = ['quantity' => 'integer'];

    public function itemReturn()
    {
        return $this->belongsTo(ItemReturn::class);
    }

    public function deliveryDetail()
    {
        return $this->belongsTo(DeliveryDetail::class);
    }

    public function item()
    {
        return $this->belongsTo(Item::class);
    }

    public function getConditionLabelAttribute(): string
    {
        return self::CONDITIONS[$this->condition] ?? $this->condition;
    }
}
