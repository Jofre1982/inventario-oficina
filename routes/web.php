<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\DeliveryController;
use App\Http\Controllers\DependencyController;
use App\Http\Controllers\EntryController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\ItemController;
use App\Http\Controllers\LoanController;
use App\Http\Controllers\OrganizationController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ProviderController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\ResponsibleController;
use App\Http\Controllers\ReturnController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Rutas web
|--------------------------------------------------------------------------
| No hay registro público: los usuarios los crea un administrador
| desde "Administrar > Usuarios".
*/

Route::middleware('guest')->group(function () {
    Route::get('login', [LoginController::class, 'show'])->name('login');
    Route::post('login', [LoginController::class, 'login'])->middleware('throttle:20,1');
});
Route::post('logout', [LoginController::class, 'logout'])->name('logout');

Route::redirect('/', '/home');

Route::middleware('auth')->group(function () {
    Route::get('home', [HomeController::class, 'index'])->name('home');

    // Perfil del usuario conectado
    Route::get('profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::put('profile/password', [ProfileController::class, 'password'])->name('profile.password');

    // Administración
    Route::resource('users', UserController::class);
    Route::resource('roles', RoleController::class);
    Route::get('organization', [OrganizationController::class, 'index'])->name('organization.index');
    Route::put('organization', [OrganizationController::class, 'update'])->name('organization.update');

    // Catálogos
    Route::resource('categories', CategoryController::class);
    Route::resource('providers', ProviderController::class);
    Route::resource('dependencies', DependencyController::class);
    Route::resource('responsibles', ResponsibleController::class);

    // Elementos
    Route::get('items/lookup', [ItemController::class, 'lookup'])->name('items.lookup');
    Route::resource('items', ItemController::class);
    Route::patch('items/{item}/status', [ItemController::class, 'change_status'])->name('change.status.items');
    Route::post('items/{item}/write-off', [ItemController::class, 'writeOff'])->name('items.writeoff');
    Route::post('items/{item}/repair', [ItemController::class, 'repair'])->name('items.repair');

    // Entradas
    Route::resource('entries', EntryController::class)->only(['index', 'create', 'store', 'show']);
    Route::get('entries/{entry}/pdf', [EntryController::class, 'pdf'])->name('entries.pdf');
    Route::patch('entries/{entry}/cancel', [EntryController::class, 'cancel'])->name('entries.cancel');

    // Salidas y préstamos
    Route::resource('deliveries', DeliveryController::class)->only(['index', 'create', 'store', 'show']);
    Route::get('deliveries/{delivery}/acta', [DeliveryController::class, 'pdf'])->name('deliveries.pdf');
    Route::patch('deliveries/{delivery}/cancel', [DeliveryController::class, 'cancel'])->name('deliveries.cancel');

    // Devoluciones
    Route::get('deliveries/{delivery}/return', [ReturnController::class, 'create'])->name('returns.create');
    Route::post('deliveries/{delivery}/return', [ReturnController::class, 'store'])->name('returns.store');
    Route::get('returns', [ReturnController::class, 'index'])->name('returns.index');
    Route::get('returns/{return}', [ReturnController::class, 'show'])->name('returns.show');
    Route::get('returns/{return}/acta', [ReturnController::class, 'pdf'])->name('returns.pdf');

    // Préstamos pendientes y vencidos
    Route::get('loans', [LoanController::class, 'index'])->name('loans.index');
    Route::get('loans/overdue', [LoanController::class, 'index'])->name('loans.overdue');

    // Reportes
    Route::get('reports/movements/{mode?}', [ReportController::class, 'movements'])->name('reports.movements')->where('mode', 'date|responsible|dependency');
    Route::get('reports/loans', [ReportController::class, 'loans'])->name('reports.loans');
});
