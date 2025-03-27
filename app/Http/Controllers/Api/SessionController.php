<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Repositories\Contracts\MovieSessionRepositoryInterface;
use App\Repositories\Contracts\SeatRepositoryInterface; // Injecter si besoin des détails des sièges
use Illuminate\Http\Request;
use App\Http\Resources\MovieSessionResource;
use App\Http\Resources\SeatResource; // Pour les sièges
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use App\Models\MovieSession; // Importer le modèle pour le findOrFail ou Route Model Binding

class SessionController extends Controller
{
    protected $sessionRepository;
    protected $seatRepository;

    public function __construct(
        MovieSessionRepositoryInterface $sessionRepository,
        SeatRepositoryInterface $seatRepository
        )
    {
        $this->sessionRepository = $sessionRepository;
        $this->seatRepository = $seatRepository;
        $this->middleware('auth:api')->except(['index', 'show', 'getAvailableSeats']);
        $this->middleware('isAdmin')->only(['store', 'update', 'destroy']);
    }

    public function index(Request $request): JsonResponse
    {
        // Ajouter logique de filtrage (par film, date, type...) via le repository
        // $filters = $request->only(['movie_id', 'date', 'type']);
        $sessions = $this->sessionRepository->allFiltered($request->all()); // Méthode à créer dans le repo
        return response()->json(MovieSessionResource::collection($sessions));
    }

    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'movie_id' => 'required|exists:movies,id',
            'hall_id' => 'required|exists:halls,id',
            'start_time' => 'required|date|after:now',
            'language' => 'required|string|max:10',
            'session_type' => ['required', new \Illuminate\Validation\Rules\Enum(\App\Enums\SessionType::class)],
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        // Ajouter vérification de non-chevauchement de séances dans la même salle

        $session = $this->sessionRepository->create($validator->validated());
        return response()->json(new MovieSessionResource($session), Response::HTTP_CREATED);
    }

    public function show(int $id): JsonResponse
    {
         try {
            $session = $this->sessionRepository->findWithDetails($id); // Méthode à créer pour charger les relations (movie, hall)
            return response()->json(new MovieSessionResource($session));
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
             return response()->json(['message' => 'Session not found'], Response::HTTP_NOT_FOUND);
        }
    }

    // --- Gestion des Sièges pour une séance ---
    public function getAvailableSeats(int $sessionId): JsonResponse
    {
         try {
            $session = $this->sessionRepository->find($sessionId); // Charger la session
            $hall = $session->hall()->with('seats')->first(); // Charger la salle et tous ses sièges
            if (!$hall) {
                 return response()->json(['message' => 'Hall not found for this session'], Response::HTTP_NOT_FOUND);
            }

            $allSeats = $hall->seats;
            $reservedSeatIds = $session->reserved_seat_ids; // Utiliser l'accesseur du modèle MovieSession

            $availableSeats = $allSeats->filter(function ($seat) use ($reservedSeatIds) {
                return !in_array($seat->id, $reservedSeatIds);
            });

            // Optionnel: Grouper par rangée pour l'affichage front-end
            $groupedSeats = $allSeats->groupBy('row_number')->map(function ($rowSeats) use ($reservedSeatIds) {
                return $rowSeats->map(function ($seat) use ($reservedSeatIds) {
                    $seatData = new SeatResource($seat); // Utiliser une ressource pour formater
                    $seatData->additional(['is_reserved' => in_array($seat->id, $reservedSeatIds)]);
                    return $seatData;
                });
            });


            // Retourner juste les sièges disponibles
            // return response()->json(SeatResource::collection($availableSeats));

            // Ou retourner tous les sièges avec leur statut
             return response()->json([
                 'hall_name' => $hall->name,
                 'session_type' => $session->session_type,
                 'seats_layout' => $groupedSeats // Retourner les sièges groupés par rangée
             ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
             return response()->json(['message' => 'Session not found'], Response::HTTP_NOT_FOUND);
        }
    }


    public function update(Request $request, int $id): JsonResponse
    {
        // Validation similaire à store, mais avec 'sometimes'
        // ...
        // Vérification de non-chevauchement
        // ...
        $updated = $this->sessionRepository->update($id, $request->all()); // Passer les données validées
        if ($updated) {
             $session = $this->sessionRepository->findWithDetails($id);
             return response()->json(new MovieSessionResource($session));
        }
        return response()->json(['message' => 'Session not found or update failed'], Response::HTTP_NOT_FOUND);
    }

    public function destroy(int $id): JsonResponse
    {
        // Attention: vérifier s'il y a des réservations payées avant de supprimer ? Ou les annuler ?
        $deleted = $this->sessionRepository->delete($id);
        if ($deleted) {
            return response()->json(null, Response::HTTP_NO_CONTENT);
        }
        return response()->json(['message' => 'Session not found'], Response::HTTP_NOT_FOUND);
    }
}