<?php

use App\Http\Controllers\UsuarioController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => redirect()->route('usuarios.index'));

// CRUD del módulo de usuarios por medio de PHP + Laravel
Route::resource('usuarios', UsuarioController::class);
