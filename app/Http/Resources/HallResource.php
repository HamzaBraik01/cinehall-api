<?php
namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class HallResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'capacity' => $this->whenAppended('capacity'), // Inclure la capacité si calculée via accesseur
            // 'seats' => SeatResource::collection($this->whenLoaded('seats')), // Ne pas charger tous les sièges par défaut ici
        ];
    }
}