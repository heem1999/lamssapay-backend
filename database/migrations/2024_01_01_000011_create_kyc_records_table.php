<?php

/**
 * NFCPay Database Migration
 * 
 * Creates the kyc_records table for storing Know Your Customer verification data.
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
        Schema::create('kyc_records', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->index();
            
            $table->enum('document_type', ['passport', 'national_id', 'driving_license', 'utility_bill']);
            $table->string('document_number', 100)->nullable();
            
            // File Paths (Encrypted/Secure storage)
            $table->string('front_image_path', 255);
            $table->string('back_image_path', 255)->nullable();
            $table->string('selfie_image_path', 255)->nullable();
            
            // Verification Status
            $table->enum('status', ['pending', 'verified', 'rejected', 'expired'])->default('pending')->index();
            $table->text('rejection_reason')->nullable();
            
            $table->timestamp('verified_at')->nullable();
            $table->unsignedBigInteger('verified_by')->nullable(); // Admin ID
            
            $table->date('expiry_date')->nullable();
            
            $table->timestamps();
            $table->softDeletes();
            
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('verified_by')->references('id')->on('users')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('kyc_records');
    }
};
