@extends('layouts.app')
@section('titulo', 'Modificar usuario')
@section('contenido')
<h2>Modificar usuario #{{ $usuario->id }}</h2>
<form method="POST" action="{{ route('usuarios.update', $usuario) }}">
    @csrf
    @method('PUT')
    <label>Nombre<input type="text" name="nombre" value="{{ old('nombre', $usuario->nombre) }}" required maxlength="100"></label>
    <label>Correo<input type="email" name="email" value="{{ old('email', $usuario->email) }}" required maxlength="150"></label>
    <label>Contraseña (vacío = conservar)<input type="password" name="password"></label>
    <p><button class="btn" type="submit">Guardar cambios</button>
    <a href="{{ route('usuarios.index') }}">Volver</a></p>
</form>
@endsection
