<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['nombre', 'email', 'password'])]
#[Hidden(['password'])]
class Usuario extends Model
{
    protected $table = 'usuarios';

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
        ];
    }
}
