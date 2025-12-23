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
            $table->string('settlement_card_token')->after('user_id');
            $table->string('device_id')->after('settlement_card_token');
            $table->string('business_name')->nullable()->change();
            $table->string('business_type')->nullable()->change();
            $table->string('business_registration_number')->nullable()->change();
            $table->string('tax_id')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('merchant_requests', function (Blueprint $table) {
            $table->dropColumn(['settlement_card_token', 'device_id']);
            $table->string('business_name')->nullable(false)->change();
        });
    }
};
