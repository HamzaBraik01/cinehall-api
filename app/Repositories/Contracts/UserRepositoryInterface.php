<?php

namespace App\Repositories\Contracts;

use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

interface UserRepositoryInterface
{
    /**
     * Récupère tous les utilisateurs (pour l'admin principalement).
     *
     * @param array $columns
     * @return Collection<int, User>
     */
    public function all(array $columns = ['*']): Collection;

    /**
     * Trouve un utilisateur par son ID.
     * Lance ModelNotFoundException si non trouvé.
     *
     * @param int $id
     * @return User
     */
    public function find(int $id): User;

    /**
     * Trouve un utilisateur par son email.
     *
     * @param string $email
     * @return User|null Retourne l'utilisateur ou null si non trouvé.
     */
    public function findByEmail(string $email): ?User;

    /**
     * Crée un nouvel utilisateur.
     * Le mot de passe doit être hashé avant d'appeler cette méthode ou géré par un mutateur sur le modèle.
     *
     * @param array $data
     * @return User
     */
    public function create(array $data): User;

    /**
     * Met à jour un utilisateur existant.
     * Le mot de passe doit être hashé avant d'appeler cette méthode ou géré par un mutateur sur le modèle si fourni.
     *
     * @param int $id
     * @param array $data
     * @return bool Retourne true si succès, false sinon.
     */
    public function update(int $id, array $data): bool;

    /**
     * Supprime un utilisateur.
     *
     * @param int $id
     * @return bool Retourne true si succès, false sinon.
     */
    public function delete(int $id): bool;
}