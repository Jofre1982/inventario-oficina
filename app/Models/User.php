<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    protected $fillable = ['name', 'email', 'password'];

    protected $hidden = ['password', 'remember_token'];

    /** Permisos ya calculados en esta petición (evita repetir consultas). */
    private ?array $permissionCache = null;

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function roles()
    {
        return $this->belongsToMany(Role::class)->orderBy('name');
    }

    /**
     * true / false según los roles; null si no tiene ninguna regla especial.
     * Un rol "all-access" gana sobre todo; "no-access" bloquea todo lo demás.
     */
    public function hasPermission(string $slug): bool
    {
        $cache = $this->permissionCache ??= $this->loadPermissions();

        if ($cache['all']) {
            return true;
        }
        if ($cache['none']) {
            return false;
        }

        return isset($cache['slugs'][$slug]);
    }

    public function isAdmin(): bool
    {
        return ($this->permissionCache ??= $this->loadPermissions())['all'];
    }

    public function forgetPermissions(): void
    {
        $this->permissionCache = null;
        $this->unsetRelation('roles');
    }

    private function loadPermissions(): array
    {
        $roles = $this->roles()->with('permissions:id,slug')->get();

        return [
            'all' => $roles->contains('special', Role::ALL_ACCESS),
            'none' => $roles->contains('special', Role::NO_ACCESS),
            'slugs' => $roles->flatMap->permissions->pluck('slug')->flip()->all(),
        ];
    }
}
