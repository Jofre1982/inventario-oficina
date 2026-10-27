<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/** Dependencia u oficina: Secretaría de Gobierno, Tesorería, etc. */
class Dependency extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'head_name', 'phone', 'active'];

    protected $casts = ['active' => 'boolean'];

    public function responsibles()
    {
        return $this->hasMany(Responsible::class);
    }

    public function deliveries()
    {
        return $this->hasMany(Delivery::class);
    }

    public function scopeActive($query)
    {
        return $query->where('active', true);
    }
}
