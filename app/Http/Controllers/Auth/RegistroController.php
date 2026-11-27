<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class RegistroController extends Controller
{
    public function create(): View
    {
        return view('auth.registro');
    }

    public function store(Request $request): RedirectResponse
    {
        $datos = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'confirmed', Password::min(8)],
        ]);

        $usuario = User::create([
            'name' => $datos['name'],
            'email' => $datos['email'],
            'password' => Hash::make($datos['password']),
        ]);

        event(new Registered($usuario));
        if (! $request->session()->has('url.intended')) {
            $request->session()->put([
                'url.intended' => route('viviendas.create'),
                'guia_primer_uso' => $usuario->id,
            ]);
        }

        return redirect()->route('login')
            ->with('exito_titulo', 'Cuenta registrada correctamente')
            ->with('exito', 'Ya puedes iniciar sesión con tu correo electrónico y contraseña.')
            ->withInput(['email' => $usuario->email]);
    }
}
