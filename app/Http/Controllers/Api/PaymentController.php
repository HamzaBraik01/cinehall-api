<?php

namespace App\Http\Controllers\Api;

// ... autres use statements ...
use Stripe\Event; // Importer Event si vous voulez typer l'objet décodé
use Illuminate\Support\Facades\Log;

class PaymentController extends Controller
{
    // ... constructeur et createPaymentIntent ...

    /**
     * Gère les événements entrants du Webhook Stripe.
     * *** VERSION NON SÉCURISÉE SANS VÉRIFICATION DE SIGNATURE - NE PAS UTILISER EN PRODUCTION ***
     */
    public function handleStripeWebhook(Request $request): Response
    {
        $payload = $request->getContent();
        Log::warning('!!! STRIPE WEBHOOK SIGNATURE VERIFICATION IS DISABLED - INSECURE !!!'); // Log d'avertissement

        try {
            // Décoder directement le JSON SANS vérification
            $event = json_decode($payload);

            // Vérifier si le décodage a fonctionné et si c'est un objet
             if (json_last_error() !== JSON_ERROR_NONE || !is_object($event)) {
                Log::error('Invalid Stripe webhook payload (JSON decode failed).');
                return response('Invalid payload', Response::HTTP_BAD_REQUEST);
            }

             // Vérifier la présence des champs attendus (basique)
             if (!isset($event->type) || !isset($event->data) || !isset($event->data->object)) {
                Log::error('Malformed Stripe webhook payload (missing type or data object).');
                 return response('Malformed payload', Response::HTTP_BAD_REQUEST);
             }

              // Optionnel : Vous POUVEZ essayer de récupérer l'événement depuis l'API Stripe
              // en utilisant l'ID de l'événement ($event->id) pour une couche de validation
              // supplémentaire, mais cela ne remplace pas la vérification de signature.
              // try {
              //   $verifiedEvent = \Stripe\Event::retrieve($event->id);
              //   // Comparer $verifiedEvent avec $event reçu ? Risqué si l'attaquant envoie un ID valide mais un payload différent.
              // } catch (\Exception $e) {
              //   Log::error('Could not retrieve event from Stripe API for verification.', ['event_id' => $event->id ?? null]);
              //   return response('Event verification failed', Response::HTTP_BAD_REQUEST);
              // }


        } catch (\Exception $e) {
             // Gérer toute autre erreur potentielle lors du traitement du payload brut
             Log::error('Error processing raw webhook payload: ' . $e->getMessage());
             return response('Error processing payload', Response::HTTP_INTERNAL_SERVER_ERROR);
        }


        // Gérer l'événement (identique à avant)
        switch ($event->type) {
            case 'payment_intent.succeeded':
                $paymentIntent = $event->data->object; // Contient l'objet PaymentIntent
                Log::info('Webhook received (INSECURE): payment_intent.succeeded', ['id' => $paymentIntent->id ?? 'N/A']);
                // Assurez-vous que $paymentIntent est bien un objet avant de l'utiliser
                 if (is_object($paymentIntent)) {
                     $this->handlePaymentIntentSucceeded($paymentIntent); // Appeler la méthode de gestion
                 } else {
                      Log::error('Webhook received (INSECURE): payment_intent.succeeded data->object is not an object.');
                 }
                break;
            // ... autres cas ...
            default:
                Log::info('Received unhandled Stripe event type (INSECURE): ' . ($event->type ?? 'N/A'));
        }

        // Répondre à Stripe pour accuser réception
        return response('Webhook Handled (Insecurely)', Response::HTTP_OK);
    }

     /**
      * Logique métier pour un paiement réussi.
      * Assurez-vous que $paymentIntent est bien l'objet attendu.
      */
     protected function handlePaymentIntentSucceeded(\stdClass $paymentIntent): void // Utiliser stdClass car on décode du JSON brut
     {
         // Récupérer l'ID de la réservation depuis les métadonnées
         // Attention: accès aux propriétés d'un stdClass
         $reservationId = $paymentIntent->metadata->reservation_id ?? null;

         if (!$reservationId) {
             Log::error('Missing reservation_id in PaymentIntent metadata (INSECURE)', ['id' => $paymentIntent->id ?? 'N/A']);
             return;
         }

        // ... le reste de la logique reste similaire, mais soyez conscient que
        // les informations dans $paymentIntent n'ont pas été vérifiées via signature ...

         try {
             $reservation = $this->reservationRepository->find((int)$reservationId);

             if ($reservation && $reservation->status !== ReservationStatus::Paid) {
                  $expectedAmount = (int)($reservation->total_price * 100);
                  // Vérifier l'amount_received qui est DANS LE PAYLOAD NON VÉRIFIÉ
                  if (isset($paymentIntent->amount_received) && $paymentIntent->amount_received === $expectedAmount) {
                     $success = $this->reservationRepository->markAsPaid($reservation->id, $paymentIntent->id ?? null);
                     if ($success) {
                          Log::info("Reservation {$reservation->id} marked as Paid via INSECURE webhook.");
                     } else {
                          Log::error("Failed to mark reservation {$reservation->id} as Paid via INSECURE webhook.");
                     }
                  } else {
                     Log::error("PaymentIntent amount mismatch for reservation {$reservation->id} (INSECURE).", [
                         'expected' => $expectedAmount,
                         'received' => $paymentIntent->amount_received ?? 'N/A'
                     ]);
                  }
             } elseif ($reservation && $reservation->status === ReservationStatus::Paid) {
                 Log::warning("Received INSECURE succeeded webhook for already Paid reservation {$reservation->id}. Ignoring.");
             } else {
                  Log::error("Reservation not found for INSECURE succeeded PaymentIntent metadata.", ['reservation_id' => $reservationId, 'payment_intent_id' => $paymentIntent->id ?? 'N/A']);
             }

         } catch (\Exception $e) {
              Log::error("Error processing INSECURE succeeded PaymentIntent webhook for reservation {$reservationId}: " . $e->getMessage(), ['payment_intent_id' => $paymentIntent->id ?? 'N/A']);
         }
     }

    // ... reste du contrôleur ...
}