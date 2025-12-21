<?php

/**
 * NFCPay Database Migration
 * 
 * Creates the cards table for storing tokenized payment card information.
 * All sensitive card data is encrypted using AES-256-GCM.
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
     * Creates the cards table for storing encrypted, tokenized payment cards.
     * Features include:
     * - AES-256 encryption for sensitive data
     * - Tokenization support
     * - Multiple cards per wallet
     * - Default card selection
     * - Card lifecycle management
     *
     * @return void
     */
    public function up(): void
    {
        Schema::create('cards', function (Blueprint $table) {
            // Primary Key
            $table->id();
            
            // Wallet Relationship
            $table->unsignedBigInteger('wallet_id')->index();
            
            // Card Token (Encrypted - from payment gateway or internal)
            $table->text('card_token'); // Encrypted token
            $table->string('token_reference', 100)->unique()->nullable(); // Gateway reference
            
            // Masked Card Information (Display Only)
            $table->string('card_last_four', 4); // Last 4 digits for display
            $table->string('card_first_six', 6)->nullable(); // First 6 (BIN) for fraud detection
            
            // Card Details
            $table->string('card_brand', 50)->index(); // Visa, Mastercard, Amex, etc.
            $table->enum('card_type', [
                'credit',
                'debit',
                'prepaid',
                'unknown'
            ])->default('unknown')->index();
            
            // Cardholder Information (Encrypted)
            $table->text('holder_name'); // Encrypted
            
            // Expiry Information (Encrypted)
            $table->text('expiry_month'); // Encrypted
            $table->text('expiry_year'); // Encrypted
            $table->date('expiry_date')->nullable(); // Calculated expiry for queries
            
            // Billing Address (Optional)
            $table->text('billing_address')->nullable(); // JSON encrypted
            $table->string('billing_zip', 10)->nullable();
            $table->string('billing_country', 2)->nullable();
            
            // Card Status
            $table->boolean('is_default')->default(false)->index();
            $table->boolean('is_active')->default(true)->index();
            $table->boolean('is_verified')->default(false);
            $table->timestamp('verified_at')->nullable();
            
            // Payment Gateway Integration (Future)
            $table->string('gateway_provider', 50)->nullable(); // stripe, braintree, etc.
            $table->text('gateway_reference')->nullable(); // Gateway-specific data
            $table->text('gateway_metadata')->nullable(); // JSON
            
            // Card Limits
            $table->decimal('daily_limit', 15, 2)->nullable();
            $table->decimal('transaction_limit', 15, 2)->nullable();
            
            // Usage Statistics
            $table->unsignedInteger('usage_count')->default(0);
            $table->timestamp('last_used_at')->nullable()->index();
            $table->decimal('total_spent', 15, 2)->default(0.00);
            
            // Security
            $table->integer('failed_attempts')->default(0);
            $table->timestamp('locked_until')->nullable();
            $table->boolean('requires_3ds')->default(false); // 3D Secure
            
            // Card Fingerprint (for duplicate detection)
            $table->string('card_fingerprint', 64)->nullable()->index();
            
            // Reason for Deactivation
            $table->enum('deactivation_reason', [
                'expired',
                'user_removed',
                'fraud_detected',
                'gateway_declined',
                'other'
            ])->nullable();
            
            // Metadata
            $table->json('metadata')->nullable();
            
            // Timestamps
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes
            $table->index(['wallet_id', 'is_default']);
            $table->index(['wallet_id', 'is_active']);
            $table->index('expiry_date');
            $table->index('created_at');
            
            // Foreign Keys
            $table->foreign('wallet_id')
                  ->references('id')
                  ->on('wallets')
                  ->onDelete('cascade');
        });
        
        DB::statement("ALTER TABLE cards COMMENT 'Tokenized and encrypted payment cards linked to wallets'");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('cards');
    }
};
