<?php

namespace App\Providers;

use App\Models\Permission;
use App\Models\User;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Schema::defaultStringLength(191);
        Paginator::useBootstrapFour();
        Carbon::setLocale(config('app.locale'));

        // Roles y permisos: @can('items.create'), middleware can:items.create, etc.
        // Solo responde por los permisos que existen en la tabla "permissions".
        Gate::before(function (User $user, string $ability) {
            static $known = null;
            $known ??= app()->runningUnitTests() ? null : Permission::pluck('id', 'slug')->all();
            $isKnown = $known !== null ? isset($known[$ability]) : Permission::where('slug', $ability)->exists();

            if ($isKnown) {
                return $user->hasPermission($ability);
            }

            return $user->isAdmin() ? true : null;
        });
    }
}
