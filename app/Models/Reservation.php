<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Reservation extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'movie_session_id',
        'status', // 'Pending', 'Paid', 'Cancelled', 'Expired'
        'expires_at',
        'total_price',
        'payment_intent_id', // Pour Stripe par exemple
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'status' => \App\Enums\ReservationStatus::class, // Utiliser un Enum
        'total_price' => 'decimal:2',
    ];

    // Relations
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function movieSession()
    {
        return $this->belongsTo(MovieSession::class);
    }

    // Relation avec Sièges (via table pivot)
    public function seats()
    {
        return $this->belongsToMany(Seat::class, 'reservation_seat');
    }
}