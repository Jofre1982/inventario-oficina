<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Historial (kárdex) de cada elemento. Lo escribe solo InventoryService.
 * quantity siempre es positiva; el tipo indica si suma o resta.
 */
class Movement extends Model
{
    public const ENTRY = 'ENTRADA';
    public const ENTRY_CANCEL = 'ANULA_ENTRADA';
    public const OUTPUT = 'SALIDA';
    public const LOAN = 'PRESTAMO';
    public const OUTPUT_CANCEL = 'ANULA_SALIDA';
    public const RETURN = 'DEVOLUCION';
    public const WRITE_OFF = 'BAJA';
    public const REPAIRED = 'REPARADO';

    public const TYPES = [
        self::ENTRY => 'Entrada',
        self::OUTPUT => 'Salida (consumo)',
        self::LOAN => 'Préstamo',
        self::RETURN => 'Devolución',
        self::WRITE_OFF => 'Baja',
        self::REPAIRED => 'Reparado (vuelve a disponible)',
        self::ENTRY_CANCEL => 'Anulación de entrada',
        self::OUTPUT_CANCEL => 'Anulación de salida',
    ];

    /** Tipos que aumentan las existencias del almacén. */
    public const INBOUND = [self::ENTRY, self::RETURN, self::OUTPUT_CANCEL];

    /** Tipos que las disminuyen. */
    public const OUTBOUND = [self::OUTPUT, self::LOAN, self::WRITE_OFF, self::ENTRY_CANCEL];

    protected $fillable = [
        'date', 'item_id', 'type', 'quantity', 'stock_after', 'unavailable_after', 'condition',
        'responsible_id', 'dependency_id', 'user_id', 'entry_id', 'delivery_id', 'item_return_id', 'notes',
    ];

    protected $casts = [
        'date' => 'date',
        'quantity' => 'integer',
        'stock_after' => 'integer',
        'unavailable_after' => 'integer',
    ];

    public function item()
    {
        return $this->belongsTo(Item::class);
    }

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

    public function entry()
    {
        return $this->belongsTo(Entry::class);
    }

    public function delivery()
    {
        return $this->belongsTo(Delivery::class);
    }

    public function itemReturn()
    {
        return $this->belongsTo(ItemReturn::class);
    }

    public function getTypeLabelAttribute(): string
    {
        return self::TYPES[$this->type] ?? $this->type;
    }

    /** +1 suma al almacén, -1 resta, 0 solo cambia de disponible/no disponible. */
    public function getDirectionAttribute(): int
    {
        return in_array($this->type, self::INBOUND, true) ? 1 : (in_array($this->type, self::OUTBOUND, true) ? -1 : 0);
    }

    public function getConditionLabelAttribute(): ?string
    {
        return $this->condition ? (ItemReturnDetail::CONDITIONS[$this->condition] ?? $this->condition) : null;
    }

    /** Texto del documento que originó el movimiento. */
    public function getReferenceAttribute(): string
    {
        return match (true) {
            (bool) $this->item_return_id => 'Devolución N.º ' . str_pad((string) $this->item_return_id, 5, '0', STR_PAD_LEFT),
            (bool) $this->delivery_id => 'Salida N.º ' . str_pad((string) $this->delivery_id, 5, '0', STR_PAD_LEFT),
            (bool) $this->entry_id => 'Entrada N.º ' . str_pad((string) $this->entry_id, 5, '0', STR_PAD_LEFT),
            default => '—',
        };
    }
}
