<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        Commands\ExpirePendingReservations::class, // Assurez-vous qu'elle est listée ici
    ];

    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void // Type retour PHP 8+
    {
        // Exécuter la commande toutes les minutes
        $schedule->command('reservations:expire-pending')
                 ->everyMinute()
                 ->withoutOverlapping() // Empêche l'exécution si la précédente n'est pas finie
                 ->runInBackground(); // Exécute en arrière-plan si possible

        // $schedule->command('inspire')->hourly();
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void // Type retour PHP 8+
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}