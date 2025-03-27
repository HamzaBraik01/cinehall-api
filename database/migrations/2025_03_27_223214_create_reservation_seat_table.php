<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('reservation_seat', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reservation_id')->constrained()->onDelete('cascade');
            $table->foreignId('seat_id')->constrained()->onDelete('cascade');
            $table->timestamps();

            // Un siège ne peut être réservé qu'une fois par réservation
            $table->unique(['reservation_id', 'seat_id']);

            // Important: Un siège ne peut être dans une réservation 'Pending' ou 'Paid'
            // qu'une seule fois pour une séance donnée. Cette logique est complexe à gérer
            // uniquement avec une contrainte de base de données ici. Elle doit être
            // appliquée lors de la création de la réservation dans le contrôleur/service.
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reservation_seat');
    }
};
