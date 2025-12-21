<?php

/**
 * NFCPay Database Migration
 * 
 * Creates the users table with comprehensive fields for authentication,
 * KYC verification, merchant status, and security features.
 * 
 * @category   Database
 * @package    NFCPay
 * @author     NFCPay Development Team
 * @license    Proprietary
 * @version    1.0.0
 * @since      2025-12-16
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * This creates the users table which serves as the core authentication
     * and user management system for NFCPay. Includes support for:
     * - Multi-factor authentication (2FA)
     * - Biometric authentication
     * - KYC verification
     * - Merchant capabilities
     * - Multi-tenancy support
     *
     * @return void
     */
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            // Primary Key
            $table->id();
            
            // Multi-tenancy support (for future platform expansion)
            $table->unsignedBigInteger('tenant_id')->nullable()->index();
            
            // Authentication Credentials
            $table->string('email', 191)->unique();
            $table->string('phone', 20)->unique()->nullable();
            $table->string('password');
            
            // Personal Information
            $table->string('first_name', 100);
            $table->string('last_name', 100);
            $table->date('date_of_birth')->nullable();
            $table->string('country_code', 2)->default('US');
            
            // KYC Verification
            $table->enum('kyc_status', [
                'pending',
                'verified', 
                'rejected'
            ])->default('pending')->index();
            $table->timestamp('kyc_verified_at')->nullable();
            $table->unsignedBigInteger('kyc_verified_by')->nullable();
            
            // Merchant Status
            $table->boolean('is_merchant')->default(false)->index();
            $table->timestamp('merchant_approved_at')->nullable();
            
            // Account Status
            $table->boolean('is_active')->default(true)->index();
            $table->boolean('is_suspended')->default(false);
            $table->text('suspension_reason')->nullable();
            $table->timestamp('suspended_at')->nullable();
            
            // Email & Phone Verification
            $table->timestamp('email_verified_at')->nullable();
            $table->timestamp('phone_verified_at')->nullable();
            $table->string('email_verification_token')->nullable();
            $table->string('phone_verification_token')->nullable();
            
            // Two-Factor Authentication
            $table->boolean('two_factor_enabled')->default(false);
            $table->text('two_factor_secret')->nullable(); // Encrypted
            $table->text('two_factor_recovery_codes')->nullable(); // Encrypted
            $table->timestamp('two_factor_confirmed_at')->nullable();
            
            // Biometric Authentication
            $table->boolean('biometric_enabled')->default(false);
            $table->string('biometric_type')->nullable(); // fingerprint, face_id
            $table->text('biometric_data')->nullable(); // Encrypted
            
            // Session & Security
            $table->timestamp('last_login_at')->nullable()->index();
            $table->ipAddress('last_login_ip')->nullable();
            $table->string('last_login_device')->nullable();
            $table->integer('failed_login_attempts')->default(0);
            $table->timestamp('locked_until')->nullable();
            
            // Password Reset
            $table->string('remember_token')->nullable();
            $table->string('password_reset_token')->nullable();
            $table->timestamp('password_reset_expires_at')->nullable();
            
            // Referral System (Future)
            $table->string('referral_code', 20)->unique()->nullable();
            $table->unsignedBigInteger('referred_by')->nullable();
            
            // Preferences
            $table->string('preferred_language', 5)->default('en');
            $table->string('timezone', 50)->default('UTC');
            $table->string('currency', 3)->default('USD');
            
            // Notifications Preferences
            $table->boolean('email_notifications')->default(true);
            $table->boolean('sms_notifications')->default(false);
            $table->boolean('push_notifications')->default(true);
            
            // Metadata
            $table->json('metadata')->nullable(); // For additional flexible data
            
            // Timestamps
            $table->timestamps(); // created_at, updated_at
            $table->softDeletes(); // deleted_at for soft delete
            
            // Indexes for Performance
            $table->index('created_at');
            $table->index(['email', 'is_active']);
            $table->index(['kyc_status', 'is_merchant']);
            
            // Foreign Key Constraints (self-referencing for referrals)
            $table->foreign('referred_by')
                  ->references('id')
                  ->on('users')
                  ->onDelete('set null');
        });
        
        // Add comment to table
        DB::statement("ALTER TABLE users COMMENT 'Core user accounts with authentication, KYC, and merchant capabilities'");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
