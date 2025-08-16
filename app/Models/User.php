<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasApiTokens,HasFactory, Notifiable;

    /**
     * Los atributos que se pueden asignar en masa
     *
     * @var list<string>
     */
    protected $fillable = [
        'name1',
        'name2',
        'surname1',
        'surname2',
        'email',
        'password',
        'rol'
    ];

    /**
     * Los atributos que deben ocultarse de la serializacion
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Obtiene los atributos que deben ser convertidos (cast).
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * Verifica si el usuario tiene rol administrador
     */
    public function isAdmin(): bool
    {
        return $this->rol === 'Administrador';
    }

    /**
     * Verifica si el usuario tiene rol Panadero
     */
    public function isBaker(): bool
    {
        return $this->rol === 'Panadero';
    }

    /**
     * Vefifica si el usuario tiene rol Cajero
     */
    public function isCashier(): bool
    {
        return $this->rol === 'Cajero';
    }
}
