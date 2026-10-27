<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Entrada de elementos al almacén (suma stock). */
class Entry extends Model
{
    public const VALID = 'VALID';
    public const CANCELED = 'CANCELED';

    protected $fillable = ['date', 'provider_id', 'document', 'notes', 'user_id', 'status', 'canceled_at'];

    protected $casts = [
        'date' => 'date',
        'canceled_at' => 'datetime',
    ];

    public function provider()
    {
        return $this->belongsTo(Provider::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function details()
    {
        return $this->hasMany(EntryDetail::class);
    }

    public function scopeValid($query)
    {
        return $query->where('status', self::VALID);
    }

    public function isValid(): bool
    {
        return $this->status === self::VALID;
    }

    public function getNumberAttribute(): string
    {
        return str_pad((string) $this->id, 5, '0', STR_PAD_LEFT);
    }
}
