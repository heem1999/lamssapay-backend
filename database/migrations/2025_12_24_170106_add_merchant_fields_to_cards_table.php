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
        Schema::table('cards', function (Blueprint $table) {
            $table->enum('merchant_status', ['CONSUMER_ONLY', 'MERCHANT_PENDING', 'MERCHANT_APPROVED', 'MERCHANT_DISABLED'])
                  ->default('CONSUMER_ONLY')
                  ->after('status');
            $table->boolean('is_settlement_default')->default(false)->after('merchant_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cards', function (Blueprint $table) {
            $table->dropColumn(['merchant_status', 'is_settlement_default']);
        });
    }
};
