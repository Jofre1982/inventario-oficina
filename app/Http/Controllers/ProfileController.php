<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class ProfileController extends Controller
{
    public function edit()
    {
        return view('profile.edit', ['user' => auth()->user()->load('roles')]);
    }

    public function update(Request $request)
    {
        $user = $request->user();
        $data = $request->validate([
            'name' => ['required', 'string', 'min:3', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users')->ignore($user->id)],
        ], [], ['name' => 'nombre', 'email' => 'correo']);

        $user->update($data);

        return back()->with('success', 'Perfil actualizado.');
    }

    public function password(Request $request)
    {
        $user = $request->user();
        $request->validate([
            'old_password' => ['required', 'current_password'],
            'password' => ['required', 'min:6', 'confirmed', 'different:old_password'],
        ], [], ['old_password' => 'contraseña actual', 'password' => 'nueva contraseña']);

        $user->update(['password' => Hash::make($request->password)]);

        return back()->with('success', 'Contraseña actualizada.');
    }
}
