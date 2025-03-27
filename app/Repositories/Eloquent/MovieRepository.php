<?php
namespace App\Repositories\Eloquent;

use App\Models\Movie;
use App\Repositories\Contracts\MovieRepositoryInterface;
use Illuminate\Database\Eloquent\Collection; // Importer
use Illuminate\Database\Eloquent\ModelNotFoundException; // Pour la gestion d'erreur

class MovieRepository implements MovieRepositoryInterface
{
    protected $model;

    public function __construct(Movie $movie)
    {
        $this->model = $movie;
    }

    public function all(array $columns = ['*']): Collection
    {
        return $this->model->get($columns);
    }

    public function find(int $id) // Peut lancer ModelNotFoundException
    {
        return $this->model->findOrFail($id);
    }

    public function create(array $data): Movie
    {
        return $this->model->create($data);
    }

    public function update(int $id, array $data): bool
    {
        try {
            $movie = $this->find($id);
            return $movie->update($data);
        } catch (ModelNotFoundException $e) {
            return false; // Ou gérer l'exception différemment
        }
    }

    public function delete(int $id): bool
    {
         try {
            $movie = $this->find($id);
            return $movie->delete();
        } catch (ModelNotFoundException $e) {
            return false;
        }
    }

    // public function findByGenre(string $genre): Collection
    // {
    //     return $this->model->where('genre', $genre)->get();
    // }
}