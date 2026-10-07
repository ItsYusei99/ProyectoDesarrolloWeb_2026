@extends('layouts.app')
@section('titulo', 'Consultar usuarios')
@section('contenido')
<h2>Consultar usuarios</h2>
<p><a class="btn" href="{{ route('usuarios.create') }}">+ Nuevo usuario</a></p>
<table>
    <thead>
        <tr><th>ID</th><th>Nombre</th><th>Correo</th><th>Acciones</th></tr>
    </thead>
    <tbody>
        @forelse ($usuarios as $u)
            <tr>
                <td>{{ $u->id }}</td>
                <td>{{ $u->nombre }}</td>
                <td>{{ $u->email }}</td>
                <td>
                    <a href="{{ route('usuarios.show', $u) }}">Ver</a> |
                    <a href="{{ route('usuarios.edit', $u) }}">Modificar</a> |
                    <form class="inline" method="POST" action="{{ route('usuarios.destroy', $u) }}">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-danger" onclick="return confirm('¿Eliminar?')">Eliminar</button>
                    </form>
                </td>
            </tr>
        @empty
            <tr><td colspan="4">Sin usuarios.</td></tr>
        @endforelse
    </tbody>
</table>
@endsection
