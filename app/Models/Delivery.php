<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * Salida de elementos a un responsable de una dependencia.
 * Puede tener consumibles (no regresan) y devolutivos (préstamo con fecha de devolución).
 */
class Delivery extends Model
{
    public const VALID = 'VALID';
    public const CANCELED = 'CANCELED';

    protected $fillable = ['date', 'due_date', 'responsible_id', 'dependency_id', 'notes', 'user_id', 'status', 'canceled_at'];

    protected $casts = [
        'date' => 'date',
        'due_date' => 'date',
        'canceled_at' => 'datetime',
    ];

    public function responsible()
    {
        return $this->belongsTo(Responsible::class);
    }

    public function dependency()
    {
        return $this->belongsTo(Dependency::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function details()
    {
        return $this->hasMany(DeliveryDetail::class);
    }

    public function returns()
    {
        return $this->hasMany(ItemReturn::class);
    }

    public function scopeValid($query)
    {
        return $query->where('status', self::VALID);
    }

    /** Salidas válidas con devolutivos aún sin devolver. */
    public function scopeWithPendingLoans($query)
    {
        return $query->valid()->whereHas('details', fn ($d) => $d
            ->where('type', Item::RETURNABLE)
            ->whereColumn('returned', '<', 'quantity'));
    }

    /** Préstamos pendientes cuya fecha de devolución ya pasó. */
    public function scopeOverdue($query)
    {
        return $query->withPendingLoans()->whereDate('due_date', '<', Carbon::today());
    }

    public function isValid(): bool
    {
        return $this->status === self::VALID;
    }

    public function getNumberAttribute(): string
    {
        return str_pad((string) $this->id, 5, '0', STR_PAD_LEFT);
    }

    public function hasReturnables(): bool
    {
        return $this->details->contains('type', Item::RETURNABLE);
    }

    public function pendingQuantity(): int
    {
        return (int) $this->details->where('type', Item::RETURNABLE)->sum(fn ($d) => $d->pending);
    }

    public function isOverdue(): bool
    {
        return $this->isValid() && $this->due_date && $this->due_date->lt(Carbon::today()) && $this->pendingQuantity() > 0;
    }

    public function overdueDays(): int
    {
        return $this->isOverdue() ? (int) $this->due_date->diffInDays(Carbon::today()) : 0;
    }

    /** [texto, color del badge] del estado del préstamo. */
    public function loanStatus(): array
    {
        if (! $this->isValid()) {
            return ['Anulada', 'danger'];
        }
        if (! $this->hasReturnables()) {
            return ['Entregada', 'info'];
        }

        $pending = $this->pendingQuantity();
        $total = (int) $this->details->where('type', Item::RETURNABLE)->sum('quantity');

        if ($pending === 0) {
            return ['Devuelto', 'success'];
        }
        if ($this->isOverdue()) {
            return ['Vencido', 'danger'];
        }

        return [$pending < $total ? 'Devuelto en parte' : 'Prestado', 'warning'];
    }
}
