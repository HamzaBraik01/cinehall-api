<?php

namespace App\Repositories\Eloquent;

use App\Models\Hall;
use App\Repositories\Contracts\HallRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class HallRepository implements HallRepositoryInterface
{
    protected $model;

    public function __construct(Hall $hall)
    {
        $this->model = $hall;
    }

    public function all(array $columns = ['*']): Collection
    {
        return $this->model->get($columns);
    }

    public function find(int $id): Hall
    {
        return $this->model->findOrFail($id);
    }

    public function findWithSeats(int $id): Hall
    {
        return $this->model->with('seats')->findOrFail($id);
    }

    public function create(array $data): Hall
    {
        return $this->model->create($data);
    }

    public function update(int $id, array $data): bool
    {
        try {
            $hall = $this->find($id);
            return $hall->update($data);
        } catch (ModelNotFoundException $e) {
            return false;
        }
    }

    public function delete(int $id): bool
    {
        try {
            $hall = $this->find($id);
            return $hall->delete();
        } catch (ModelNotFoundException $e) {
            return false;
        }
    }
}