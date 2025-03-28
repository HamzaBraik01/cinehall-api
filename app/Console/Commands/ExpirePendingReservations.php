<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Reservation; // Importer le modèle
use App\Enums\ReservationStatus; // Importer l'Enum
use Carbon\Carbon; // Importer Carbon
use Illuminate\Support\Facades\Log; // Pour logger l'exécution

class ExpirePendingReservations extends Command
{
    protected $signature = 'reservations:expire-pending';
    protected $description = 'Mark pending reservations as expired if their expiration time has passed';

    public function handle(): int // Type de retour PHP 8+
    {
        $this->info('Starting expiration check for pending reservations...');
        Log::info('Running ExpirePendingReservations command.');

        $now = Carbon::now();
        $expiredCount = 0;

        // Trouver les réservations 'Pending' dont 'expires_at' est passé
        $reservationsToExpire = Reservation::where('status', ReservationStatus::Pending)
                                           ->where('expires_at', '<=', $now)
                                           ->get();

        if ($reservationsToExpire->isEmpty()) {
            $this->info('No pending reservations to expire.');
            Log::info('No pending reservations found to expire.');
            return Command::SUCCESS;
        }

        foreach ($reservationsToExpire as $reservation) {
            // Mettre à jour le statut en 'Expired'
            // Utiliser le repository serait préférable ici
            $reservation->status = ReservationStatus::Expired;
            $reservation->save();
            $expiredCount++;
            $this->line("Reservation ID {$reservation->id} expired.");
            Log::info("Reservation ID {$reservation->id} marked as expired.");

            // Optionnel: Détacher les sièges (si non géré par cascade)
            // $reservation->seats()->detach();

            // Optionnel: Annuler l'intention de paiement Stripe associée
            // if ($reservation->payment_intent_id) { try { ... cancel intent ... } catch() {} }
        }

        $this->info("Finished. Expired {$expiredCount} reservations.");
        Log::info("ExpirePendingReservations command finished. Expired {$expiredCount} reservations.");

        return Command::SUCCESS; // Retourner 0 en cas de succès
    }
}