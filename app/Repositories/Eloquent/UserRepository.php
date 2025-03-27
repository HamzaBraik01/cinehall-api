<?php

namespace App\Repositories\Eloquent;

use App\Models\User;
use App\Repositories\Contracts\UserRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\ModelNotFoundException;
// Il n'est pas nécessaire d'importer Hash ici si le modèle User utilise le cast 'hashed' pour le password

class UserRepository implements UserRepositoryInterface
{
    protected $model;

    public function __construct(User $user)
    {
        $this->model = $user;
    }

    public function all(array $columns = ['*']): Collection
    {
        // Exclure les admins si nécessaire par défaut ? Ou filtrer plus tard.
        return $this->model->get($columns);
    }

    public function find(int $id): User
    {
        return $this->model->findOrFail($id);
    }

    public function findByEmail(string $email): ?User
    {
        return $this->model->where('email', $email)->first();
    }

    public function create(array $data): User
    {
        // Le hashage du mot de passe est géré par le cast 'hashed' dans le modèle User
        // si $data['password'] est fourni en clair.
        return $this->model->create($data);
    }

    public function update(int $id, array $data): bool
    {
        try {
            $user = $this->find($id);
             // Le hashage du mot de passe (si $data['password'] est présent)
             // est géré par le cast 'hashed' dans le modèle User.
            return $user->update($data);
        } catch (ModelNotFoundException $e) {
            return false;
        }
    }

    public function delete(int $id): bool
    {
        try {
            $user = $this->find($id);
            // Gérer la suppression des réservations associées ? (cascade configurée dans la migration)
            return $user->delete();
        } catch (ModelNotFoundException $e) {
            return false;
        }
    }
}