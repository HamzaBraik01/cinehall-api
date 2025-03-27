<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ReservationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'status' => $this->status->value,
            'total_price' => $this->total_price,
            'expires_at' => $this->expires_at?->format('Y-m-d H:i:s'), // Gérer le cas null
            'created_at' => $this->created_at->format('Y-m-d H:i:s'),
            'user' => new UserResource($this->whenLoaded('user')),
            'session' => new MovieSessionResource($this->whenLoaded('movieSession')),
            'seats' => SeatResource::collection($this->whenLoaded('seats')),
            // 'client_secret' => $this->when(isset($this->additional['client_secret']), $this->additional['client_secret'] ?? null), // Pour Stripe
        ];
    }
}