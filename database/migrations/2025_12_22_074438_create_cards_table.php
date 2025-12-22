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
        Schema::create('cards', function (Blueprint $table) {
            $table->id();
            $table->foreignId('device_id')->constrained('devices')->onDelete('cascade');
            $table->string('token_reference')->unique(); // The safe token from the gateway
            $table->string('masked_pan', 4); // Only last 4 digits
            $table->string('scheme'); // Visa, Mastercard, Mada
            $table->string('card_art')->nullable(); // URL or local asset ID
            $table->boolean('is_default')->default(false);
            $table->string('fingerprint')->index(); // Hash of PAN to detect duplicates
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cards');
    }
};
