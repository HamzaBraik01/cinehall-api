<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Tymon\JWTAuth\Contracts\JWTSubject; // Importer

class User extends Authenticatable implements JWTSubject // Implémenter
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'is_admin', // Ajouter si vous avez un rôle admin simple
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'is_admin' => 'boolean', // Caster en booléen
    ];

    // Relation avec Réservations
    public function reservations()
    {
        return $this->hasMany(Reservation::class);
    }

    // --- Méthodes JWT ---
    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    public function getJWTCustomClaims()
    {
        return []; // Ajouter des claims personnalisés si nécessaire (ex: rôle)
        // return ['is_admin' => $this->is_admin];
    }
}