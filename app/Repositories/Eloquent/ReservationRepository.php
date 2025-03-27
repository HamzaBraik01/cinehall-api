<?php

namespace App\Repositories\Eloquent;

use App\Models\Reservation;
use App\Repositories\Contracts\ReservationRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use App\Enums\ReservationStatus;
use Illuminate\Support\Facades\DB; // Pour la requête des sièges réservés

class ReservationRepository implements ReservationRepositoryInterface
{
    protected $model;

    public function __construct(Reservation $reservation)
    {
        $this->model = $reservation;
    }

    public function find(int $id): Reservation
    {
        return $this->model->findOrFail($id);
    }

    public function findUserReservationOrFail(int $id, int $userId): Reservation
    {
        // Charger les relations utiles par défaut
        return $this->model
            ->with(['seats', 'movieSession.movie', 'movieSession.hall'])
            ->where('id', $id)
            ->where('user_id', $userId)
            ->firstOrFail();
    }

    public function findByUser(int $userId): Collection
    {
        // Charger les relations utiles et trier par date de création
        return $this->model
            ->with(['seats', 'movieSession.movie', 'movieSession.hall'])
            ->where('user_id', $userId)
            ->latest() // Trie par created_at descendant
            ->get();
    }

    public function create(array $data): Reservation
    {
        return $this->model->create($data);
    }

    public function updateStatus(int $id, ReservationStatus $status): bool
    {
        try {
            $reservation = $this->find($id);
            // Utiliser la méthode update pour s'assurer que les événements Eloquent sont déclenchés
            return $reservation->update(['status' => $status]);
        } catch (ModelNotFoundException $e) {
            return false;
        }
    }

     public function update(int $id, array $data): bool
     {
         try {
             $reservation = $this->find($id);
             return $reservation->update($data);
         } catch (ModelNotFoundException $e) {
             return false;
         }
     }

    public function delete(int $id): bool
    {
        // Normalement, on ne supprime pas une réservation, on l'annule (change status).
        // Cette méthode est incluse pour la complétude du CRUD si nécessaire.
        try {
            $reservation = $this->find($id);
            return $reservation->delete();
        } catch (ModelNotFoundException $e) {
            return false;
        }
    }

    public function getCurrentlyReservedSeatIds(int $movieSessionId): array
    {
        // Alternative à l'accesseur du modèle MovieSession, requête directe
        return DB::table('reservations')
            ->join('reservation_seat', 'reservations.id', '=', 'reservation_seat.reservation_id')
            ->where('reservations.movie_session_id', $movieSessionId)
            ->where(function ($query) {
                // Statut Payé OU Statut Pending ET non expiré
                $query->where('reservations.status', ReservationStatus::Paid->value)
                      ->orWhere(function ($q) {
                          $q->where('reservations.status', ReservationStatus::Pending->value)
                            ->where('reservations.expires_at', '>', now());
                      });
            })
            ->pluck('reservation_seat.seat_id') // Obtenir seulement les IDs des sièges
            ->unique() // Assurer l'unicité
            ->all(); // Retourner un tableau PHP simple
    }

    public function findPendingExpired(): Collection
    {
         return $this->model
             ->where('status', ReservationStatus::Pending)
             ->where('expires_at', '<=', now())
             ->get();
    }
}