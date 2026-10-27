<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DeliveryDetail extends Model
{
    public $timestamps = false;

    protected $fillable = ['delivery_id', 'item_id', 'type', 'quantity', 'returned'];

    protected $casts = [
        'quantity' => 'integer',
        'returned' => 'integer',
    ];

    public function delivery()
    {
        return $this->belongsTo(Delivery::class);
    }

    public function item()
    {
        return $this->belongsTo(Item::class);
    }

    public function returnDetails()
    {
        return $this->hasMany(ItemReturnDetail::class);
    }

    /** Devolutivos de salidas válidas que aún no regresan completos. */
    public function scopePendingLoans($query)
    {
        return $query->where('delivery_details.type', Item::RETURNABLE)
            ->whereColumn('delivery_details.returned', '<', 'delivery_details.quantity')
            ->whereHas('delivery', fn ($d) => $d->valid());
    }

    public function isReturnable(): bool
    {
        return $this->type === Item::RETURNABLE;
    }

    public function getPendingAttribute(): int
    {
        return $this->isReturnable() ? max($this->quantity - $this->returned, 0) : 0;
    }

    public function getTypeLabelAttribute(): string
    {
        return Item::TYPES[$this->type] ?? $this->type;
    }
}
