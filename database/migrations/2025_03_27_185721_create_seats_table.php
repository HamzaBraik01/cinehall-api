<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Enums\SeatType;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('seats', function (Blueprint $table) {
            $table->id();
            $table->foreignId('hall_id')->constrained()->onDelete('cascade');
            $table->string('row_number'); // Peut être une lettre ou un chiffre
            $table->integer('seat_number');
            $table->enum('type', array_column(SeatType::cases(), 'value'))->default(SeatType::Normal->value);
            $table->timestamps();

            $table->unique(['hall_id', 'row_number', 'seat_number']); // Un siège est unique dans une salle
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('seats');
    }
};
