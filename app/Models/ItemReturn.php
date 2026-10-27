<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Devolución de elementos devolutivos de una salida. */
class ItemReturn extends Model
{
    protected $fillable = ['date', 'delivery_id', 'notes', 'user_id'];

    protected $casts = ['date' => 'date'];

    public function delivery()
    {
        return $this->belongsTo(Delivery::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function details()
    {
        return $this->hasMany(ItemReturnDetail::class);
    }

    public function getNumberAttribute(): string
    {
        return str_pad((string) $this->id, 5, '0', STR_PAD_LEFT);
    }
}
