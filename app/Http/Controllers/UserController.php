<?php

namespace App\Http\Controllers;

use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class UserController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return static::permissions('users');
    }

    public function index()
    {
        $users = User::with('roles')->orderBy('name')->paginate(25);

        return view('users.index', compact('users'));
    }

    public function create()
    {
        return view('users.create', ['user' => new User(), 'roles' => Role::orderBy('name')->get()]);
    }

    public function store(Request $request)
    {
        $data = $this->validateUser($request);

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
        ]);
        $user->roles()->sync($data['roles'] ?? []);

        return redirect()->route('users.index')->with('success', 'Usuario creado.');
    }

    public function show(User $user)
    {
        $user->load('roles');
        $deliveries = \App\Models\Delivery::where('user_id', $user->id)->count();
        $entries = \App\Models\Entry::where('user_id', $user->id)->count();

        return view('users.show', compact('user', 'deliveries', 'entries'));
    }

    public function edit(User $user)
    {
        return view('users.edit', ['user' => $user->load('roles'), 'roles' => Role::orderBy('name')->get()]);
    }

    public function update(Request $request, User $user)
    {
        $data = $this->validateUser($request, $user);

        $user->fill(['name' => $data['name'], 'email' => $data['email']]);
        if (! empty($data['password'])) {
            $user->password = Hash::make($data['password']);
        }
        $user->save();

        // Evita que un administrador se quite a sí mismo el acceso por error.
        if ($user->id !== $request->user()->id || ! empty($data['roles'])) {
            $user->roles()->sync($data['roles'] ?? []);
        }

        return redirect()->route('users.index')->with('success', 'Usuario actualizado.');
    }

    public function destroy(Request $request, User $user)
    {
        if ($user->id === $request->user()->id) {
            return back()->with('error', 'No puede eliminar su propio usuario.');
        }

        try {
            \Illuminate\Support\Facades\DB::transaction(function () use ($user) {
                $user->roles()->detach();
                $user->delete();
            });
        } catch (\Illuminate\Database\QueryException $e) {
            return back()->with('error', 'No se puede eliminar: el usuario tiene entradas, salidas o devoluciones registradas. Puede quitarle los roles para bloquearlo.');
        }

        return redirect()->route('users.index')->with('success', 'Usuario eliminado.');
    }

    private function validateUser(Request $request, ?User $user = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'min:3', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user)],
            'password' => [$user ? 'nullable' : 'required', 'confirmed', 'min:6'],
            'roles' => ['nullable', 'array'],
            'roles.*' => ['integer', 'exists:roles,id'],
        ], [], ['name' => 'nombre', 'email' => 'correo', 'password' => 'contraseña', 'roles' => 'roles']);
    }
}
