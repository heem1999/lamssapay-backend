<?php

/**
 * NFCPay Database Migration
 * 
 * Creates the devices table for tracking user devices and managing sessions.
 * 
 * @category   Database
 * @package    NFCPay
 * @author     NFCPay Development Team
 * @license    Proprietary
 * @version    1.0.0
 * @since      2025-12-17
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::create('devices', function (Blueprint $table) {
            $table->id();
            // Nullable user_id for anonymous device identity (Phase 1)
            $table->unsignedBigInteger('user_id')->nullable()->index();
            
            $table->string('device_id', 100)->unique(); // Unique hardware ID or generated UUID
            $table->string('device_name', 100)->nullable(); // e.g., "John's iPhone 13"
            $table->string('platform', 50)->nullable(); // iOS, Android, Web
            $table->string('os_version', 50)->nullable();
            
            $table->string('fcm_token', 255)->nullable(); // Firebase Cloud Messaging Token
            
            $table->boolean('is_trusted')->default(false);
            $table->string('status', 20)->default('active'); // active, blocked
            $table->ipAddress('last_ip')->nullable();
            $table->timestamp('last_active_at')->nullable();
            
            $table->timestamps();
            
            // Foreign key constraint (if user_id is present)
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('devices');
    }
};
