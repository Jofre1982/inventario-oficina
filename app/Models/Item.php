<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

/**
 * Elemento del almacén.
 *  - CONSUMABLE (consumible): sale y no regresa (papel, tóner, aseo).
 *  - RETURNABLE (devolutivo): se presta y debe regresar (portátiles, herramientas).
 *
 * stock             = unidades disponibles en el almacén.
 * unavailable_stock = unidades devueltas dañadas o incompletas (en almacén, no se prestan).
 * Lo prestado se calcula con las salidas pendientes de devolución.
 */
class Item extends Model
{
    use HasFactory;

    public const CONSUMABLE = 'CONSUMABLE';
    public const RETURNABLE = 'RETURNABLE';

    public const TYPES = [
        self::CONSUMABLE => 'Consumible',
        self::RETURNABLE => 'Devolutivo',
    ];

    public const UNITS = ['Unidad', 'Resma', 'Caja', 'Paquete', 'Rollo', 'Galón', 'Litro', 'Kilo', 'Par', 'Juego', 'Metro', 'Frasco', 'Bolsa'];

    protected $fillable = [
        'code', 'name', 'type', 'category_id', 'unit', 'stock', 'unavailable_stock', 'min_stock',
        'serial', 'location', 'description', 'image', 'active',
    ];

    protected $casts = [
        'stock' => 'integer',
        'unavailable_stock' => 'integer',
        'min_stock' => 'integer',
        'active' => 'boolean',
    ];

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function movements()
    {
        return $this->hasMany(Movement::class);
    }

    public function deliveryDetails()
    {
        return $this->hasMany(DeliveryDetail::class);
    }

    public function scopeActive($query)
    {
        return $query->where('active', true);
    }

    public function scopeOfType($query, ?string $type)
    {
        return $type ? $query->where('type', $type) : $query;
    }

    /** Elementos que llegaron al mínimo configurado (solo si tienen mínimo). */
    public function scopeLowStock($query)
    {
        return $query->where('min_stock', '>', 0)->whereColumn('stock', '<=', 'min_stock');
    }

    /** Agrega la columna "loaned": unidades prestadas pendientes de devolución. */
    public function scopeWithLoaned($query)
    {
        if (is_null($query->getQuery()->columns)) {
            $query->select('items.*');
        }

        return $query->addSelect(['loaned' => DeliveryDetail::query()
            ->selectRaw('COALESCE(SUM(delivery_details.quantity - delivery_details.returned), 0)')
            ->join('deliveries', 'deliveries.id', '=', 'delivery_details.delivery_id')
            ->whereColumn('delivery_details.item_id', 'items.id')
            ->where('deliveries.status', Delivery::VALID)
            ->where('delivery_details.type', self::RETURNABLE),
        ]);
    }

    /** Búsqueda por nombre, código de barras o serial/placa. */
    public function scopeSearch($query, ?string $term)
    {
        $term = trim((string) $term);
        if ($term === '') {
            return $query;
        }

        return $query->where(fn ($w) => $w
            ->where('name', 'like', "%{$term}%")
            ->orWhere('code', $term)
            ->orWhere('serial', 'like', "%{$term}%"));
    }

    public function isReturnable(): bool
    {
        return $this->type === self::RETURNABLE;
    }

    public function getTypeLabelAttribute(): string
    {
        return self::TYPES[$this->type] ?? $this->type;
    }

    public function getLoanedQuantityAttribute(): int
    {
        if (array_key_exists('loaned', $this->attributes)) {
            return (int) $this->attributes['loaned'];
        }

        return (int) $this->deliveryDetails()
            ->join('deliveries', 'deliveries.id', '=', 'delivery_details.delivery_id')
            ->where('deliveries.status', Delivery::VALID)
            ->where('delivery_details.type', self::RETURNABLE)
            ->sum(DB::raw('delivery_details.quantity - delivery_details.returned'));
    }

    public function getIsLowAttribute(): bool
    {
        return $this->min_stock > 0 && $this->stock <= $this->min_stock;
    }

    public function getImageUrlAttribute(): ?string
    {
        return $this->image ? asset('image/items/' . $this->image) : null;
    }
}
