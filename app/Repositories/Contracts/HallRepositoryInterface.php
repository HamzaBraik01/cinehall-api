<?php

namespace App\Repositories\Contracts;

use App\Models\Hall;
use Illuminate\Database\Eloquent\Collection;

interface HallRepositoryInterface
{
    /**
     * Récupère toutes les salles.
     *
     * @param array $columns
     * @return Collection<int, Hall>
     */
    public function all(array $columns = ['*']): Collection;

    /**
     * Trouve une salle par son ID.
     * Lance ModelNotFoundException si non trouvée.
     *
     * @param int $id
     * @return Hall
     */
    public function find(int $id): Hall;

     /**
      * Trouve une salle par son ID avec ses sièges.
      * Lance ModelNotFoundException si non trouvée.
      *
      * @param int $id
      * @return Hall
      */
     public function findWithSeats(int $id): Hall;

    /**
     * Crée une nouvelle salle.
     *
     * @param array $data
     * @return Hall
     */
    public function create(array $data): Hall;

    /**
     * Met à jour une salle existante.
     *
     * @param int $id
     * @param array $data
     * @return bool Retourne true si succès, false sinon.
     */
    public function update(int $id, array $data): bool;

    /**
     * Supprime une salle.
     *
     * @param int $id
     * @return bool Retourne true si succès, false sinon.
     */
    public function delete(int $id): bool;
}