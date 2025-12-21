<?php

/**
 * NFCPay Database Migration
 * 
 * Creates the api_keys table for partner authentication.
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
        Schema::create('api_keys', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('partner_id')->index();
            
            $table->string('key_name', 100);
            $table->string('api_key', 64)->unique(); // Public part
            $table->string('api_secret_hash', 255); // Hashed secret
            
            $table->enum('environment', ['development', 'staging', 'production'])->default('development');
            $table->json('scopes')->nullable();
            
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_used_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('revoked_at')->nullable();
            
            $table->timestamps();
            
            $table->foreign('partner_id')->references('id')->on('partners')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('api_keys');
    }
};
