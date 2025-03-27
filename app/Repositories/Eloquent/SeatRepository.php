<?php

namespace App\Repositories\Eloquent;

use App\Models\Seat;
use App\Repositories\Contracts\SeatRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class SeatRepository implements SeatRepositoryInterface
{
    protected $model;

    public function __construct(Seat $seat)
    {
        $this->model = $seat;
    }

    public function all(array $columns = ['*']): Collection
    {
        return $this->model->get($columns);
    }

    public function find(int $id): Seat
    {
        return $this->model->findOrFail($id);
    }

     public function findMany(array $ids): Collection
     {
         return $this->model->whereIn('id', $ids)->get();
     }

    public function findByHall(int $hallId): Collection
    {
        return $this->model->where('hall_id', $hallId)->orderBy('row_number')->orderBy('seat_number')->get();
    }

    public function create(array $data): Seat
    {
        return $this->model->create($data);
    }

    public function update(int $id, array $data): bool
    {
        try {
            $seat = $this->find($id);
            return $seat->update($data);
        } catch (ModelNotFoundException $e) {
            return false;
        }
    }

    public function delete(int $id): bool
    {
         try {
            $seat = $this->find($id);
            return $seat->delete();
        } catch (ModelNotFoundException $e) {
            return false;
        }
    }
}