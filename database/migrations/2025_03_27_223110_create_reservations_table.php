<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Enums\ReservationStatus;
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('reservations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('movie_session_id')->constrained('movie_sessions')->onDelete('cascade');
            $table->enum('status', array_column(ReservationStatus::cases(), 'value'))->default(ReservationStatus::Pending->value);
            $table->timestamp('expires_at')->nullable(); // Pour l'expiration automatique
            $table->decimal('total_price', 8, 2)->default(0.00);
            $table->string('payment_intent_id')->nullable()->index(); // Pour lier au paiement (ex: Stripe)
            $table->timestamps();

            $table->index(['user_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reservations');
    }
};
