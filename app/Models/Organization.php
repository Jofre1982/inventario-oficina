<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Datos de la alcaldía u oficina: salen en el menú, las actas y los reportes. */
class Organization extends Model
{
    public const DEFAULT_TERMS = 'El funcionario que recibe se hace responsable del buen uso, cuidado y custodia de los elementos relacionados en esta acta. Los elementos devolutivos deben ser devueltos al almacén en el mismo estado en que se reciben, salvo el desgaste natural por su uso, a más tardar en la fecha indicada. La pérdida o daño por mal uso será informado a la dependencia competente.';

    protected $fillable = [
        'name', 'nit', 'address', 'phone', 'email', 'city', 'logo',
        'keeper_name', 'keeper_title', 'delivery_terms',
    ];

    private static ?self $current = null;

    /** Siempre hay un solo registro; se crea si no existe. */
    public static function current(): self
    {
        if (static::$current && static::$current->exists) {
            return static::$current;
        }

        return static::$current = static::query()->first() ?? static::create([
            'name' => config('app.name'),
            'keeper_title' => 'Almacenista',
            'delivery_terms' => self::DEFAULT_TERMS,
        ]);
    }

    public static function forgetCurrent(): void
    {
        static::$current = null;
    }

    protected static function booted(): void
    {
        static::saved(fn () => static::forgetCurrent());
    }

    public function getLogoPathAttribute(): ?string
    {
        return $this->logo && is_file(public_path('image/' . $this->logo)) ? public_path('image/' . $this->logo) : null;
    }

    public function getLogoUrlAttribute(): ?string
    {
        return $this->logo_path ? asset('image/' . $this->logo) : null;
    }

    public function getTermsAttribute(): string
    {
        return trim((string) $this->delivery_terms) !== '' ? $this->delivery_terms : self::DEFAULT_TERMS;
    }
}
