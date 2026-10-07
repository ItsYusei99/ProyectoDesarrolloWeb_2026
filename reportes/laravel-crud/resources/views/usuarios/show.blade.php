@extends('layouts.app')
@section('titulo', 'Detalle de usuario')
@section('contenido')
<h2>Usuario #{{ $usuario->id }}</h2>
<p><strong>Nombre:</strong> {{ $usuario->nombre }}</p>
<p><strong>Correo:</strong> {{ $usuario->email }}</p>
<p><strong>Creado:</strong> {{ $usuario->created_at }}</p>
<p><a href="{{ route('usuarios.edit', $usuario) }}">Modificar</a> |
<a href="{{ route('usuarios.index') }}">Volver</a></p>
@endsection
