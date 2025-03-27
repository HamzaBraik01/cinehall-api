<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Movie extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'description',
        'image_path',
        'duration_minutes',
        'min_age',
        'trailer_url',
        'genre', // Ou 'genre_id' si vous avez une table Genre
        // 'release_date', // Exemple d'attribut supplémentaire
    ];

    // Relation avec Séances
    public function sessions()
    {
        return $this->hasMany(MovieSession::class);
    }

    // Si vous avez une table Genre:
    // public function genre()
    // {
    //     return $this->belongsTo(Genre::class);
    // }
}