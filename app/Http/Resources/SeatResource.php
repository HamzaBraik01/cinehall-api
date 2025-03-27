<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SeatResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $data = [
            'id' => $this->id,
            'row' => $this->row_number,
            'number' => $this->seat_number,
            'type' => $this->type->value,
            // 'hall_id' => $this->hall_id, // Peut-être pas nécessaire si dans le contexte de la salle/séance
        ];

         // Pour ajouter des données conditionnellement (comme 'is_reserved')
         if (isset($this->additional['is_reserved'])) {
            $data['is_reserved'] = $this->additional['is_reserved'];
         }

        return $data;

    }
}