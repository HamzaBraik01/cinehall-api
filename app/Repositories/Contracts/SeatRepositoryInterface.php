<?php

namespace App\Repositories\Contracts;

use App\Models\Seat;
use Illuminate\Database\Eloquent\Collection;

interface SeatRepositoryInterface
{
    /**
     * Récupère tous les sièges (potentiellement beaucoup, utiliser avec prudence).
     *
     * @param array $columns
     * @return Collection<int, Seat>
     */
    public function all(array $columns = ['*']): Collection;

    /**
     * Trouve un siège par son ID.
     * Lance ModelNotFoundException si non trouvé.
     *
     * @param int $id
     * @return Seat
     */
    public function find(int $id): Seat;

    /**
     * Trouve plusieurs sièges par leurs IDs.
     *
     * @param array $ids
     * @return Collection<int, Seat>
     */
    public function findMany(array $ids): Collection;


    /**
     * Trouve les sièges appartenant à une salle spécifique.
     *
     * @param int $hallId
     * @return Collection<int, Seat>
     */
    public function findByHall(int $hallId): Collection;

    /**
     * Crée un nouveau siège.
     *
     * @param array $data Doit inclure hall_id, row_number, seat_number, type.
     * @return Seat
     */
    public function create(array $data): Seat;

    /**
     * Met à jour un siège existant.
     *
     * @param int $id
     * @param array $data
     * @return bool Retourne true si succès, false sinon.
     */
    public function update(int $id, array $data): bool;

    /**
     * Supprime un siège.
     *
     * @param int $id
     * @return bool Retourne true si succès, false sinon.
     */
    public function delete(int $id): bool;
}