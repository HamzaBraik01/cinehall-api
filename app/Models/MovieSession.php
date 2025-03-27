<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MovieSession extends Model
{
    use HasFactory;

    protected $table = 'movie_sessions'; // Nom explicite si différent du pluriel du modèle

    protected $fillable = [
        'movie_id',
        'hall_id',
        'start_time',
        'language',
        'session_type', // 'Normal', 'VIP'
    ];

    protected $casts = [
        'start_time' => 'datetime',
        'session_type' => \App\Enums\SessionType::class, // Utiliser un Enum
    ];

    // Relations
    public function movie()
    {
        return $this->belongsTo(Movie::class);
    }

    public function hall()
    {
        return $this->belongsTo(Hall::class);
    }

    public function reservations()
    {
        return $this->hasMany(Reservation::class);
    }

    // Helper pour obtenir les sièges réservés pour cette séance
    public function getReservedSeatIdsAttribute()
    {
        return Reservation::where('movie_session_id', $this->id)
            ->whereIn('status', [\App\Enums\ReservationStatus::Paid, \App\Enums\ReservationStatus::Pending])
            // ->where(function ($query) { // Uniquement les Pending non expirées
            //     $query->where('status', \App\Enums\ReservationStatus::Paid)
            //           ->orWhere(function ($q) {
            //               $q->where('status', \App\Enums\ReservationStatus::Pending)
            //                 ->where('expires_at', '>', now());
            //           });
            // })
            ->with('seats:id') // Charger uniquement l'ID des sièges liés
            ->get()
            ->pluck('seats.*.id') // Récupérer tous les IDs des sièges de toutes les réservations
            ->flatten() // Aplatir la collection d'IDs
            ->unique() // Assurer l'unicité
            ->toArray(); // Convertir en tableau
    }

}