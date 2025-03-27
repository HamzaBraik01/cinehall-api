<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Hall extends Model
{
    use HasFactory;

    protected $fillable = ['name']; // La capacité peut être calculée

    // Relation avec Sièges
    public function seats()
    {
        return $this->hasMany(Seat::class);
    }

    // Relation avec Séances
    public function sessions()
    {
        return $this->hasMany(MovieSession::class);
    }

    // Accesseur pour calculer la capacité
    public function getCapacityAttribute()
    {
        return $this->seats()->count();
    }
}