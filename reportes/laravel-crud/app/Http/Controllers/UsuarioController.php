<?php

namespace App\Http\Controllers;

use App\Models\Usuario;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class UsuarioController extends Controller
{
    // Read — consultar usuarios
    public function index(): View
    {
        $usuarios = Usuario::orderBy('id')->get();
        return view('usuarios.index', compact('usuarios'));
    }

    // Create — formulario
    public function create(): View
    {
        return view('usuarios.create');
    }

    // Create — guardar
    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'nombre'   => 'required|string|max:100',
            'email'    => 'required|email|max:150|unique:usuarios,email',
            'password' => 'required|string|min:8',
        ]);

        Usuario::create($data);

        return redirect()->route('usuarios.index')
            ->with('ok', 'Usuario creado.');
    }

    // Read — ver uno
    public function show(Usuario $usuario): View
    {
        return view('usuarios.show', compact('usuario'));
    }

    // Update — formulario
    public function edit(Usuario $usuario): View
    {
        return view('usuarios.edit', compact('usuario'));
    }

    // Update — guardar cambios (contraseña opcional)
    public function update(Request $request, Usuario $usuario): RedirectResponse
    {
        $data = $request->validate([
            'nombre'   => 'required|string|max:100',
            'email'    => ['required', 'email', 'max:150', Rule::unique('usuarios', 'email')->ignore($usuario->id)],
            'password' => 'nullable|string|min:8',
        ]);

        if (empty($data['password'])) {
            unset($data['password']);
        }

        $usuario->update($data);

        return redirect()->route('usuarios.index')
            ->with('ok', 'Usuario actualizado.');
    }

    // Delete — eliminar
    public function destroy(Usuario $usuario): RedirectResponse
    {
        $usuario->delete();

        return redirect()->route('usuarios.index')
            ->with('ok', 'Usuario eliminado.');
    }
}
