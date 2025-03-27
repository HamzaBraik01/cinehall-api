<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage; // Pour l'URL de l'image

class MovieResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'description' => $this->description,
            'image_url' => $this->image_path ? Storage::disk('public')->url($this->image_path) : null, // Générer l'URL publique
            'duration_minutes' => $this->duration_minutes,
            'min_age' => $this->min_age,
            'trailer_url' => $this->trailer_url,
            'genre' => $this->genre,
            // 'sessions' => MovieSessionResource::collection($this->whenLoaded('sessions')), // Charger si demandé
            'created_at' => $this->created_at->toDateTimeString(),
            'updated_at' => $this->updated_at->toDateTimeString(),
        ];
    }
}