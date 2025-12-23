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
        Schema::create('ledger_entries', function (Blueprint $table) {
            $table->id();
            $table->uuid('ledger_id')->unique();
            $table->string('transaction_id')->unique();
            $table->string('device_id')->nullable();
            $table->string('card_token');
            $table->string('merchant_id')->nullable();
            $table->decimal('amount', 15, 2);
            $table->string('currency', 3);
            $table->string('direction');
            $table->string('status');
            $table->string('auth_code')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ledger_entries');
    }
};
