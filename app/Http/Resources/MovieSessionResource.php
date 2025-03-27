<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MovieSessionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'start_time' => $this->start_time->format('Y-m-d H:i:s'), // Format spécifique
            'language' => $this->language,
            'session_type' => $this->session_type->value, // Valeur de l'enum
            'movie' => new MovieResource($this->whenLoaded('movie')), // Inclure le film si chargé
            'hall' => new HallResource($this->whenLoaded('hall')),     // Inclure la salle si chargée
            // Ajouter d'autres infos si nécessaire (ex: places restantes approx.)
        ];
    }
}