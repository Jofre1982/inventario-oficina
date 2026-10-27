<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/** Funcionario o persona que recibe elementos. */
class Responsible extends Model
{
    use HasFactory;

    protected $fillable = ['document', 'name', 'position', 'dependency_id', 'phone', 'email', 'active'];

    protected $casts = ['active' => 'boolean'];

    public function dependency()
    {
        return $this->belongsTo(Dependency::class);
    }

    public function deliveries()
    {
        return $this->hasMany(Delivery::class);
    }

    public function scopeActive($query)
    {
        return $query->where('active', true);
    }

    /** Líneas de devolutivos que esta persona aún no ha devuelto. */
    public function pendingLoans()
    {
        return DeliveryDetail::pendingLoans()
            ->whereHas('delivery', fn ($q) => $q->where('responsible_id', $this->id))
            ->with(['item', 'delivery.dependency'])
            ->get()
            ->sortBy(fn ($d) => $d->delivery->due_date?->timestamp ?? PHP_INT_MAX)
            ->values();
    }
}
