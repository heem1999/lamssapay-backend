<?php

/**
 * NFCPay Database Migration
 * 
 * Creates the merchants table for storing merchant business information.
 * Merchants are approved users who can receive payments via NFC or wallet.
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
     * Creates the merchants table for business profiles and settings.
     * Features include:
     * - Business verification and approval workflow
     * - Commission and settlement configuration
     * - Branding customization
     * - Transaction limits and restrictions
     *
     * @return void
     */
    public function up(): void
    {
        Schema::create('merchants', function (Blueprint $table) {
            // Primary Key
            $table->id();
            
            // User Relationship (One-to-One)
            $table->unsignedBigInteger('user_id')->unique();
            
            // Merchant Identifier
            $table->string('merchant_id', 50)->unique(); // Public ID: MER_ABC123
            
            // Business Information
            $table->string('business_name', 255);
            $table->string('business_legal_name', 255)->nullable();
            $table->string('business_type', 100)->nullable(); // retail, restaurant, service, etc.
            $table->string('business_category', 100)->nullable(); // MCC code category
            $table->string('business_mcc', 4)->nullable(); // Merchant Category Code
            
            // Registration Details
            $table->string('business_registration_number', 100)->nullable();
            $table->string('tax_id', 50)->nullable(); // EIN, VAT, etc.
            $table->string('business_registration_country', 2)->default('US');
            
            // Business Address
            $table->text('business_address')->nullable();
            $table->string('business_city', 100)->nullable();
            $table->string('business_state', 100)->nullable();
            $table->string('business_postal_code', 20)->nullable();
            $table->string('business_country', 2)->default('US');
            
            // Contact Information
            $table->string('business_phone', 20)->nullable();
            $table->string('business_email', 255)->nullable();
            $table->string('business_website', 255)->nullable();
            $table->text('business_description', 1000)->nullable();
            
            // Documents (KYB - Know Your Business)
            $table->json('business_documents')->nullable(); // Array of document paths
            $table->boolean('documents_verified')->default(false);
            $table->timestamp('documents_verified_at')->nullable();
            
            // Merchant Status
            $table->enum('status', [
                'pending',      // Application submitted
                'under_review', // Being reviewed by admin
                'active',       // Approved and active
                'suspended',    // Temporarily suspended
                'rejected',     // Application rejected
                'closed'        // Merchant closed account
            ])->default('pending')->index();
            
            // Approval Information
            $table->timestamp('approved_at')->nullable();
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->text('approval_notes')->nullable();
            
            // Rejection Information
            $table->timestamp('rejected_at')->nullable();
            $table->unsignedBigInteger('rejected_by')->nullable();
            $table->text('rejection_reason')->nullable();
            
            // Suspension Information
            $table->timestamp('suspended_at')->nullable();
            $table->unsignedBigInteger('suspended_by')->nullable();
            $table->text('suspension_reason')->nullable();
            
            // Wallet Reference
            $table->unsignedBigInteger('wallet_id')->nullable()->unique();
            
            // Commission & Fees
            $table->decimal('commission_rate', 5, 2)->default(1.50); // Percentage
            $table->decimal('commission_fixed', 10, 2)->default(0.00); // Fixed amount per transaction
            $table->decimal('minimum_commission', 10, 2)->default(0.00);
            $table->decimal('maximum_commission', 10, 2)->nullable();
            
            // Settlement Configuration
            $table->enum('settlement_frequency', [
                'daily',
                'weekly',
                'bi_weekly',
                'monthly'
            ])->default('weekly');
            
            $table->decimal('minimum_settlement_amount', 15, 2)->default(10.00);
            $table->unsignedInteger('settlement_delay_days')->default(0); // Rolling reserve period
            
            // Banking Information (for settlements)
            $table->text('bank_account_holder')->nullable(); // Encrypted
            $table->text('bank_account_number')->nullable(); // Encrypted
            $table->text('bank_routing_number')->nullable(); // Encrypted
            $table->string('bank_name', 255)->nullable();
            $table->string('bank_country', 2)->nullable();
            $table->boolean('bank_verified')->default(false);
            
            // Transaction Limits
            $table->decimal('transaction_limit', 15, 2)->nullable(); // Max per transaction
            $table->decimal('daily_limit', 15, 2)->nullable();
            $table->decimal('monthly_limit', 15, 2)->nullable();
            
            // Statistics
            $table->unsignedBigInteger('total_transactions')->default(0);
            $table->decimal('total_volume', 15, 2)->default(0.00);
            $table->decimal('total_commission_earned', 15, 2)->default(0.00);
            $table->decimal('lifetime_volume', 15, 2)->default(0.00);
            $table->timestamp('first_transaction_at')->nullable();
            $table->timestamp('last_transaction_at')->nullable();
            
            // Branding & Customization
            $table->string('branding_logo', 255)->nullable(); // Logo URL/path
            $table->string('branding_color', 7)->nullable(); // Hex color code
            $table->string('branding_tagline', 255)->nullable();
            $table->json('branding_settings')->nullable(); // Additional branding options
            
            // Operating Hours
            $table->json('operating_hours')->nullable(); // JSON structure for weekly hours
            $table->string('timezone', 50)->default('UTC');
            
            // Payment Methods Accepted
            $table->boolean('accepts_nfc')->default(true);
            $table->boolean('accepts_wallet')->default(true);
            $table->boolean('accepts_cards')->default(false); // Future
            
            // Features & Capabilities
            $table->boolean('can_issue_refunds')->default(true);
            $table->boolean('can_access_api')->default(false); // Platform API access
            $table->boolean('requires_pin_for_payments')->default(false);
            $table->decimal('pin_required_above_amount', 15, 2)->nullable();
            
            // Rating & Reviews (Future)
            $table->decimal('rating', 3, 2)->default(0.00); // 0.00 to 5.00
            $table->unsignedInteger('total_reviews')->default(0);
            
            // Risk & Compliance
            $table->enum('risk_category', [
                'low',
                'medium',
                'high'
            ])->default('medium')->index();
            
            $table->boolean('requires_enhanced_monitoring')->default(false);
            $table->timestamp('last_compliance_review_at')->nullable();
            
            // Metadata
            $table->json('metadata')->nullable();
            
            // Timestamps
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes
            $table->index(['status', 'created_at']);
            $table->index('business_category');
            $table->index('created_at');
            
            // Foreign Keys
            $table->foreign('user_id')
                  ->references('id')
                  ->on('users')
                  ->onDelete('cascade');
                  
            $table->foreign('wallet_id')
                  ->references('id')
                  ->on('wallets')
                  ->onDelete('set null');
                  
            $table->foreign('approved_by')
                  ->references('id')
                  ->on('users')
                  ->onDelete('set null');
                  
            $table->foreign('rejected_by')
                  ->references('id')
                  ->on('users')
                  ->onDelete('set null');
                  
            $table->foreign('suspended_by')
                  ->references('id')
                  ->on('users')
                  ->onDelete('set null');
        });
        
        DB::statement("ALTER TABLE merchants COMMENT 'Merchant business profiles with payment acceptance capabilities'");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('merchants');
    }
};
