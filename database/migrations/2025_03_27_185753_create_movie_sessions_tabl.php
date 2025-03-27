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
        Schema::create('movie_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('movie_id')->constrained()->onDelete('cascade');
            $table->foreignId('hall_id')->constrained()->onDelete('cascade');
            $table->dateTime('start_time');
            $table->string('language')->default('VF'); // Version Française par défaut
            $table->enum('session_type', array_column(SessionType::cases(), 'value'))->default(SessionType::Normal->value);
            $table->timestamps();

            // Optionnel: index pour recherche rapide
            $table->index(['movie_id', 'start_time']);
            $table->index(['hall_id', 'start_time']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('movie_sessions_tabl');
    }
};
