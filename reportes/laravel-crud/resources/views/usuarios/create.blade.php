@extends('layouts.app')
@section('titulo', 'Crear usuario')
@section('contenido')
<h2>Crear usuario</h2>
<form method="POST" action="{{ route('usuarios.store') }}">
    @csrf
    <label>Nombre<input type="text" name="nombre" value="{{ old('nombre') }}" required maxlength="100"></label>
    <label>Correo<input type="email" name="email" value="{{ old('email') }}" required maxlength="150"></label>
    <label>Contraseña (mínimo 8 caracteres)<input type="password" name="password" required></label>
    <p><button class="btn" type="submit">Guardar</button>
    <a href="{{ route('usuarios.index') }}">Volver</a></p>
</form>
@endsection
