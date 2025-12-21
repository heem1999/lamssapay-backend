<?php

/**
 * NFCPay Database Migration
 * 
 * Creates the merchant_requests table for tracking merchant applications.
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
        Schema::create('merchant_requests', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->index();
            
            // Business Details
            $table->string('business_name', 255);
            $table->string('business_type', 100)->nullable();
            $table->string('business_registration_number', 100)->nullable();
            $table->string('tax_id', 50)->nullable();
            
            // Contact Info
            $table->string('business_email', 255)->nullable();
            $table->string('business_phone', 20)->nullable();
            $table->text('business_address')->nullable();
            
            // Documents
            $table->json('documents')->nullable(); // Paths to uploaded docs
            
            // Status
            $table->enum('status', [
                'pending',
                'under_review',
                'approved',
                'rejected',
                'more_info_required'
            ])->default('pending')->index();
            
            // Review Details
            $table->text('admin_notes')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->unsignedBigInteger('reviewed_by')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            
            $table->timestamps();
            $table->softDeletes();
            
            // Foreign Keys
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('reviewed_by')->references('id')->on('users')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('merchant_requests');
    }
};
