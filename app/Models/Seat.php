<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Seat extends Model
{
    use HasFactory;

    protected $fillable = [
        'hall_id',
        'row_number',
        'seat_number',
        'type', // 'Normal', 'Couple'
    ];

    protected $casts = [
        'type' => \App\Enums\SeatType::class, // Utiliser un Enum pour la robustesse
    ];

    // Relation avec Salle
    public function hall()
    {
        return $this->belongsTo(Hall::class);
    }

    // Relation avec Réservations (via table pivot)
    public function reservations()
    {
        return $this->belongsToMany(Reservation::class, 'reservation_seat');
    }
}