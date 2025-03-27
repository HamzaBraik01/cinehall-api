<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Repositories\Contracts\ReservationRepositoryInterface;
use App\Repositories\Contracts\MovieSessionRepositoryInterface;
use App\Repositories\Contracts\SeatRepositoryInterface;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB; // Pour les transactions
use Illuminate\Support\Facades\Validator;
use App\Http\Resources\ReservationResource;
use App\Models\MovieSession; // Importer pour récupérer la séance
use App\Models\Seat; // Importer pour récupérer les sièges
use App\Enums\ReservationStatus;
use App\Enums\SeatType;
use App\Enums\SessionType;
use Carbon\Carbon; // Pour la date d'expiration
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;


class ReservationController extends Controller
{
    protected $reservationRepository;
    protected $sessionRepository;
    protected $seatRepository;

    // Temps d'expiration en minutes
    const RESERVATION_EXPIRATION_MINUTES = 15;

    public function __construct(
        ReservationRepositoryInterface $reservationRepository,
        MovieSessionRepositoryInterface $sessionRepository,
        SeatRepositoryInterface $seatRepository
    ) {
        $this->reservationRepository = $reservationRepository;
        $this->sessionRepository = $sessionRepository;
        $this->seatRepository = $seatRepository;
        $this->middleware('auth:api'); // Toutes les actions de réservation nécessitent une connexion
    }

    // Lister les réservations de l'utilisateur connecté
    public function index(): JsonResponse
    {
        $user = Auth::user();
        $reservations = $this->reservationRepository->findByUser($user->id); // Méthode à créer dans le repo
        return response()->json(ReservationResource::collection($reservations));
    }


    // Créer une nouvelle réservation
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'movie_session_id' => 'required|integer|exists:movie_sessions,id',
            'seat_ids' => 'required|array|min:1',
            'seat_ids.*' => 'required|integer|exists:seats,id', // Vérifier que les IDs de siège existent
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $validatedData = $validator->validated();
        $user = Auth::user();
        $sessionId = $validatedData['movie_session_id'];
        $requestedSeatIds = $validatedData['seat_ids'];

        try {
            // Utiliser une transaction pour garantir l'atomicité
            return DB::transaction(function () use ($sessionId, $requestedSeatIds, $user) {

                // 1. Récupérer la séance et les sièges demandés AVEC VERROUILLAGE
                // Verrouiller la session pour éviter les conflits si qqn d'autre réserve en même temps
                $session = MovieSession::with('hall')->findOrFail($sessionId); // Pas besoin de lockForUpdate ici, on check les sièges

                 // Verrouiller les lignes des sièges demandés pour éviter double réservation
                 // Note: lockForUpdate fonctionne mieux sur la table pivot si possible, ou nécessite une gestion prudente
                 $seats = Seat::whereIn('id', $requestedSeatIds)->where('hall_id', $session->hall_id)->get();
                 // $seats = Seat::whereIn('id', $requestedSeatIds)->where('hall_id', $session->hall_id)->lockForUpdate()->get();

                 if (count($seats) !== count($requestedSeatIds)) {
                    throw new \Exception("One or more selected seats do not exist or do not belong to the session's hall.");
                 }

                // 2. Vérifier la disponibilité des sièges demandés
                $reservedSeatIds = $this->reservationRepository->getCurrentlyReservedSeatIds($sessionId);
                $alreadyReserved = array_intersect($requestedSeatIds, $reservedSeatIds);

                if (!empty($alreadyReserved)) {
                    throw new \Exception("Seats with IDs " . implode(', ', $alreadyReserved) . " are already reserved or pending payment.");
                }

                 // 3. Vérifier la logique VIP / Couple
                 if ($session->session_type === SessionType::VIP) {
                     $coupleSeatsRequested = $seats->where('type', SeatType::Couple);
                     $normalSeatsRequested = $seats->where('type', SeatType::Normal);

                     if ($coupleSeatsRequested->isNotEmpty()) {
                         // Vérifier que les sièges couple sont demandés par paires
                         if ($coupleSeatsRequested->count() % 2 !== 0) {
                             throw new \Exception("VIP Couple seats must be booked in pairs.");
                         }
                         // Logique plus complexe: vérifier que les paires sont adjacentes ou correspondent à une "unité couple" prédéfinie si nécessaire
                     }
                     // On peut aussi interdire de mixer sièges normaux et couple dans une résa VIP, ou ajuster le prix
                 } else { // Séance Normale
                     $coupleSeatsRequested = $seats->where('type', SeatType::Couple);
                     if ($coupleSeatsRequested->isNotEmpty()) {
                         throw new \Exception("Couple seats are only available in VIP sessions.");
                     }
                 }


                // 4. Calculer le prix total (logique à définir : prix par siège, par type...)
                $totalPrice = $this->calculateTotalPrice($session, $seats); // Méthode helper à créer

                // 5. Créer la réservation
                $reservation = $this->reservationRepository->create([
                    'user_id' => $user->id,
                    'movie_session_id' => $sessionId,
                    'status' => ReservationStatus::Pending,
                    'expires_at' => Carbon::now()->addMinutes(self::RESERVATION_EXPIRATION_MINUTES),
                    'total_price' => $totalPrice,
                ]);

                // 6. Lier les sièges à la réservation (table pivot)
                $reservation->seats()->attach($requestedSeatIds);

                // 7. (Optionnel) Créer une intention de paiement Stripe ici
                // $paymentIntent = $this->createStripePaymentIntent($reservation);
                // $reservation->update(['payment_intent_id' => $paymentIntent->id]);

                // 8. Retourner la réservation créée (avec le client_secret si Stripe est utilisé)
                $reservation->load('seats', 'movieSession.movie', 'movieSession.hall'); // Charger relations pour la réponse

                $responseResource = new ReservationResource($reservation);
                 // if (isset($paymentIntent)) {
                 //    $responseResource->additional(['client_secret' => $paymentIntent->client_secret]);
                 // }

                return response()->json($responseResource, Response::HTTP_CREATED);

            }); // Fin de la transaction

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['message' => 'Session not found.'], Response::HTTP_NOT_FOUND);
        } catch (\Exception $e) {
            // Capturer les erreurs de validation métier ou de disponibilité
            return response()->json(['message' => $e->getMessage()], Response::HTTP_CONFLICT); // 409 Conflict est approprié
        }
    }

    // Voir une réservation spécifique de l'utilisateur
    public function show(int $reservationId): JsonResponse
    {
        $user = Auth::user();
        try {
            $reservation = $this->reservationRepository->findUserReservationOrFail($reservationId, $user->id); // Méthode repo qui vérifie l'appartenance
            return response()->json(new ReservationResource($reservation->load('seats', 'movieSession.movie', 'movieSession.hall')));
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
             return response()->json(['message' => 'Reservation not found or does not belong to the user.'], Response::HTTP_NOT_FOUND);
        }
    }


    // Annuler une réservation (si statut Pending)
    public function cancel(int $reservationId): JsonResponse
    {
        $user = Auth::user();
        try {
            return DB::transaction(function () use ($reservationId, $user) {
                $reservation = $this->reservationRepository->findUserReservationOrFail($reservationId, $user->id);

                if ($reservation->status !== ReservationStatus::Pending) {
                    throw new \Exception("Only pending reservations can be cancelled.");
                }

                // Mettre à jour le statut
                $this->reservationRepository->updateStatus($reservation->id, ReservationStatus::Cancelled);

                // Détacher les sièges (la table pivot est liée par cascade onDelete sur reservation_id, mais on peut le faire explicitement)
                // $reservation->seats()->detach(); // Pas nécessaire si cascade configuré

                // Optionnel: Annuler l'intention de paiement Stripe si elle existe
                // if ($reservation->payment_intent_id) {
                //     $this->cancelStripePaymentIntent($reservation->payment_intent_id);
                // }

                return response()->json(['message' => 'Reservation successfully cancelled.']);
            });

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['message' => 'Reservation not found or does not belong to the user.'], Response::HTTP_NOT_FOUND);
        } catch (\Exception $e) {
             return response()->json(['message' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }
    }


    // --- Méthodes Helper ---

    /**
     * Calcule le prix total basé sur la session et les sièges.
     * Logique de prix à implémenter.
     */
    protected function calculateTotalPrice(MovieSession $session, $seats): float
    {
        // Exemple simple: Prix fixe par siège + supplément VIP
        $basePricePerSeat = 10.00; // À configurer
        $vipSupplement = 5.00;    // À configurer

        $totalPrice = 0;
        foreach ($seats as $seat) {
            $price = $basePricePerSeat;
            if ($session->session_type === SessionType::VIP) {
                // Le supplément pourrait s'appliquer à tous les sièges VIP ou juste aux 'Couple'
                 $price += $vipSupplement;
            }
            $totalPrice += $price;
        }
        return $totalPrice;
    }

    // protected function createStripePaymentIntent(Reservation $reservation) { ... }
    // protected function cancelStripePaymentIntent(string $paymentIntentId) { ... }

}