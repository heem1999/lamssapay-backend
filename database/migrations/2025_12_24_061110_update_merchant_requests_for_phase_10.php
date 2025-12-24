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
        Schema::table('merchant_requests', function (Blueprint $table) {
            $table->string('device_id')->nullable()->index()->after('id');
            $table->string('settlement_card_token')->nullable()->after('device_id');
            $table->unsignedBigInteger('user_id')->nullable()->change();
            $table->string('business_name')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('merchant_requests', function (Blueprint $table) {
            $table->dropColumn(['device_id', 'settlement_card_token']);
            $table->unsignedBigInteger('user_id')->nullable(false)->change();
            $table->string('business_name')->nullable(false)->change();
        });
    }
};
