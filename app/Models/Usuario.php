<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;

class Usuario extends Authenticatable
{
    use HasApiTokens;

    protected $table = 'usuarios';

    protected $fillable = [
        'id_persona',
        'correo',
        'pass',
        'admin'
    ];

    protected $hidden = [
        'pass'
    ];

    public function getAuthPassword()
    {
        return $this->pass;
    }
}
