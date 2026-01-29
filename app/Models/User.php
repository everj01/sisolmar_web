<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $table = 'sw_usuarios';

    protected $fillable = [
        'usuario', 'clave', 'nombre_1', 'nombre_2', 'apellido_1', 'apellido_2', 'tipo_rol'
    ];

    protected $primaryKey = 'codigo';
    public $timestamps = false;

    protected $hidden = [
        'clave',
        'remember_token',
    ];
    /*
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];*/
}
