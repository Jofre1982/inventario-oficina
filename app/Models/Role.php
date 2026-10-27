<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Role extends Model
{
    public const ALL_ACCESS = 'all-access';
    public const NO_ACCESS = 'no-access';

    protected $fillable = ['name', 'slug', 'description', 'special'];

    public function permissions()
    {
        return $this->belongsToMany(Permission::class);
    }

    public function users()
    {
        return $this->belongsToMany(User::class);
    }
}
