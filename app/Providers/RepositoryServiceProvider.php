<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
// Importer toutes les interfaces et implémentations
use App\Repositories\Contracts\MovieRepositoryInterface;
use App\Repositories\Eloquent\MovieRepository;
use App\Repositories\Contracts\HallRepositoryInterface;
use App\Repositories\Eloquent\HallRepository;
use App\Repositories\Contracts\SeatRepositoryInterface;
use App\Repositories\Eloquent\SeatRepository;
use App\Repositories\Contracts\MovieSessionRepositoryInterface;
use App\Repositories\Eloquent\MovieSessionRepository;
use App\Repositories\Contracts\ReservationRepositoryInterface;
use App\Repositories\Eloquent\ReservationRepository;
use App\Repositories\Contracts\UserRepositoryInterface;
use App\Repositories\Eloquent\UserRepository;


class RepositoryServiceProvider extends ServiceProvider
{
    public function register(): void // Ajouter le type de retour void
    {
        $this->app->bind(MovieRepositoryInterface::class, MovieRepository::class);
        $this->app->bind(HallRepositoryInterface::class, HallRepository::class);
        $this->app->bind(SeatRepositoryInterface::class, SeatRepository::class);
        $this->app->bind(MovieSessionRepositoryInterface::class, MovieSessionRepository::class);
        $this->app->bind(ReservationRepositoryInterface::class, ReservationRepository::class);
        $this->app->bind(UserRepositoryInterface::class, UserRepository::class);
        // ... lier les autres ...
    }

    public function boot(): void // Ajouter le type de retour void
    {
        //
    }
}