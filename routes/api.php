<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\MovieController;
use App\Http\Controllers\Api\SessionController;
use App\Http\Controllers\Api\HallController; // Assurez-vous de le créer
use App\Http\Controllers\Api\ReservationController;
use App\Http\Controllers\Api\PaymentController; // Assurez-vous de le créer
use App\Http\Controllers\Api\TicketController; // Assurez-vous de le créer
use App\Http\Controllers\Api\Admin\AdminUserController; // Assurez-vous de le créer
use App\Http\Controllers\Api\Admin\AdminDashboardController; // Assurez-vous de le créer

// --- Authentification ---
Route::group(['prefix' => 'auth'], function () {
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth:api');
    Route::post('/refresh', [AuthController::class, 'refresh'])->middleware('auth:api');
    Route::get('/me', [AuthController::class, 'me'])->middleware('auth:api');
    Route::put('/profile', [AuthController::class, 'updateProfile'])->middleware('auth:api'); // Maj profil
    Route::delete('/profile', [AuthController::class, 'deleteAccount'])->middleware('auth:api'); // Suppr compte
});

// --- Routes Publiques ---
Route::get('/movies', [MovieController::class, 'index']);
Route::get('/movies/{id}', [MovieController::class, 'show'])->where('id', '[0-9]+'); // Forcer ID numérique

Route::get('/sessions', [SessionController::class, 'index']); // Filtrage via query params
Route::get('/sessions/{id}', [SessionController::class, 'show'])->where('id', '[0-9]+');
Route::get('/sessions/{id}/seats', [SessionController::class, 'getAvailableSeats'])->where('id', '[0-9]+'); // Obtenir sièges et statut


// --- Routes Authentifiées (Utilisateurs connectés) ---
Route::middleware('auth:api')->group(function() {
    // Réservations
    Route::get('/reservations', [ReservationController::class, 'index']); // Mes réservations
    Route::post('/reservations', [ReservationController::class, 'store']); // Créer une réservation
    Route::get('/reservations/{id}', [ReservationController::class, 'show'])->where('id', '[0-9]+'); // Voir ma réservation
    Route::delete('/reservations/{id}/cancel', [ReservationController::class, 'cancel'])->where('id', '[0-9]+'); // Annuler ma réservation (si Pending)

    // Paiements (Structure - à implémenter)
    // Route::post('/reservations/{id}/pay', [PaymentController::class, 'createPaymentIntent'])->where('id', '[0-9]+');

    // Tickets (Structure - après paiement)
    Route::get('/reservations/{id}/ticket', [TicketController::class, 'downloadTicket'])->where('id', '[0-9]+'); // Télécharger mon ticket (si payé)

    // Favoris (Bonus)
    // ...
});


// --- Routes Administrateur ---
Route::middleware(['auth:api', 'isAdmin'])->prefix('admin')->group(function() { // 'isAdmin' est le nom du middleware à créer
    // CRUD Films (index et show sont publics)
    Route::post('/movies', [MovieController::class, 'store']);
    Route::put('/movies/{id}', [MovieController::class, 'update'])->where('id', '[0-9]+');
    // Route::patch('/movies/{id}', [MovieController::class, 'update'])->where('id', '[0-9]+'); // PATCH pour maj partielle
    Route::delete('/movies/{id}', [MovieController::class, 'destroy'])->where('id', '[0-9]+');

    // CRUD Séances (index et show sont publics)
    Route::post('/sessions', [SessionController::class, 'store']);
    Route::put('/sessions/{id}', [SessionController::class, 'update'])->where('id', '[0-9]+');
    Route::delete('/sessions/{id}', [SessionController::class, 'destroy'])->where('id', '[0-9]+');

    // CRUD Salles
    Route::apiResource('/halls', HallController::class); // Génère index, store, show, update, destroy

    // Gestion des sièges d'une salle (peut être intégré à HallController ou dédié)
    // Route::post('/halls/{hallId}/seats', [SeatController::class, 'store']);
    // ... autres routes pour sièges

    // Dashboard Stats (Structure)
    Route::get('/dashboard/stats', [AdminDashboardController::class, 'getStats']);

    // Gestion Utilisateurs par Admin (Structure)
    Route::apiResource('/users', AdminUserController::class);

});


// --- Webhooks (Ex: Stripe) ---
// Ces routes ne doivent PAS être dans le groupe 'api' pour éviter le middleware par défaut
// Et elles doivent exclure la protection CSRF si gérée globalement
Route::post('/webhooks/stripe', [PaymentController::class, 'handleStripeWebhook']);