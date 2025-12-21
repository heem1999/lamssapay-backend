<?php

/**
 * NFCPay Database Migration
 * 
 * Creates the partners table for platform integrations.
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
        Schema::create('partners', function (Blueprint $table) {
            $table->id();
            
            $table->string('partner_id', 50)->unique(); // Public ID
            $table->string('name', 255);
            $table->string('email', 191)->unique();
            $table->string('website', 255)->nullable();
            
            $table->enum('status', ['active', 'suspended', 'inactive'])->default('active')->index();
            $table->enum('tier', ['basic', 'premium', 'enterprise'])->default('basic');
            
            // Integration Config
            $table->string('webhook_url', 255)->nullable();
            $table->string('callback_url', 255)->nullable();
            $table->json('allowed_scopes')->nullable();
            $table->json('ip_whitelist')->nullable();
            
            // Limits
            $table->integer('rate_limit')->default(1000); // Requests per hour
            $table->boolean('is_sandbox')->default(false);
            
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('partners');
    }
};
