<?php

namespace App\Repositories\Contracts;

use App\Models\Reservation;
use Illuminate\Database\Eloquent\Collection;
use App\Enums\ReservationStatus; // Importer l'Enum

interface ReservationRepositoryInterface
{
    /**
     * Trouve une réservation par son ID.
     * Lance ModelNotFoundException si non trouvée.
     *
     * @param int $id
     * @return Reservation
     */
    public function find(int $id): Reservation;

    /**
     * Trouve une réservation spécifique appartenant à un utilisateur.
     * Lance ModelNotFoundException si non trouvée ou n'appartient pas à l'utilisateur.
     *
     * @param int $id L'ID de la réservation.
     * @param int $userId L'ID de l'utilisateur.
     * @return Reservation
     */
    public function findUserReservationOrFail(int $id, int $userId): Reservation;

    /**
     * Récupère toutes les réservations d'un utilisateur donné.
     *
     * @param int $userId
     * @return Collection<int, Reservation>
     */
    public function findByUser(int $userId): Collection;

    /**
     * Crée une nouvelle réservation.
     *
     * @param array $data Doit inclure user_id, movie_session_id, status, expires_at, total_price.
     * @return Reservation
     */
    public function create(array $data): Reservation;

    /**
     * Met à jour le statut d'une réservation.
     *
     * @param int $id
     * @param ReservationStatus $status Le nouveau statut.
     * @return bool Retourne true si succès, false sinon.
     */
    public function updateStatus(int $id, ReservationStatus $status): bool;

     /**
      * Met à jour les données d'une réservation.
      *
      * @param int $id
      * @param array $data Les données à mettre à jour (ex: payment_intent_id, status).
      * @return bool Retourne true si succès, false sinon.
      */
     public function update(int $id, array $data): bool;


    /**
     * Supprime une réservation (utilisation limitée, préférer l'annulation).
     *
     * @param int $id
     * @return bool Retourne true si succès, false sinon.
     */
    public function delete(int $id): bool;

    /**
     * Récupère les IDs des sièges actuellement réservés (payés ou en attente non expirés) pour une séance.
     *
     * @param int $movieSessionId
     * @return array<int>
     */
    public function getCurrentlyReservedSeatIds(int $movieSessionId): array;

    /**
     * Trouve les réservations en attente dont la date d'expiration est passée.
     *
     * @return Collection<int, Reservation>
     */
    public function findPendingExpired(): Collection;
}