<?php

/**
 * NFCPay Database Migration
 * 
 * Creates the transactions table for recording all payment activities.
 * Supports NFC payments, wallet transfers, refunds, and settlements.
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
     * Creates the transactions table for comprehensive transaction tracking.
     * Features include:
     * - Multi-type transaction support (payment, transfer, refund, settlement)
     * - Idempotency for payment safety
     * - Complete audit trail
     * - NFC session data storage
     * - Fee calculation and tracking
     *
     * @return void
     */
    public function up(): void
    {
        Schema::create('transactions', function (Blueprint $table) {
            // Primary Key
            $table->id();
            
            // Transaction Identifiers
            $table->string('transaction_id', 100)->unique(); // Public identifier: TXN_ABC123
            $table->string('idempotency_key', 191)->unique()->nullable(); // For duplicate prevention
            $table->string('reference_number', 50)->nullable()->index(); // External reference
            
            // Transaction Type & Status
            $table->enum('type', [
                'payment',      // NFC or card payment
                'transfer',     // P2P wallet transfer
                'refund',       // Refund transaction
                'settlement',   // Merchant settlement
                'fee',          // Fee collection
                'adjustment'    // Manual adjustment
            ])->index();
            
            $table->enum('status', [
                'pending',
                'processing',
                'completed',
                'failed',
                'cancelled',
                'refunded',
                'partially_refunded'
            ])->default('pending')->index();
            
            // Wallet References
            $table->unsignedBigInteger('from_wallet_id')->nullable()->index();
            $table->unsignedBigInteger('to_wallet_id')->nullable()->index();
            
            // User References (for easier querying)
            $table->unsignedBigInteger('from_user_id')->nullable()->index();
            $table->unsignedBigInteger('to_user_id')->nullable()->index();
            
            // Merchant Reference
            $table->unsignedBigInteger('merchant_id')->nullable()->index();
            
            // Amount Details
            $table->decimal('amount', 15, 2); // Original transaction amount
            $table->decimal('fee', 15, 2)->default(0.00); // Transaction fee
            $table->decimal('merchant_commission', 15, 2)->default(0.00); // Merchant commission
            $table->decimal('net_amount', 15, 2); // Amount after fees
            $table->string('currency', 3)->default('USD')->index();
            
            // Payment Method
            $table->enum('payment_method', [
                'nfc',
                'wallet',
                'card',
                'bank_transfer',
                'other'
            ])->nullable()->index();
            
            // Card Reference (if card payment)
            $table->unsignedBigInteger('card_id')->nullable()->index();
            
            // Description & Notes
            $table->string('description', 500)->nullable();
            $table->text('notes')->nullable(); // Internal notes
            $table->text('customer_note')->nullable(); // Customer-facing note
            
            // Metadata & Session Data
            $table->json('metadata')->nullable(); // Additional flexible data
            $table->json('nfc_data')->nullable(); // NFC session details
            $table->json('device_data')->nullable(); // Device information
            
            // Error Handling
            $table->string('error_code', 50)->nullable()->index();
            $table->text('error_message')->nullable();
            $table->text('gateway_response')->nullable(); // Payment gateway response
            
            // Refund Information
            $table->unsignedBigInteger('parent_transaction_id')->nullable(); // For refunds
            $table->decimal('refunded_amount', 15, 2)->default(0.00);
            $table->timestamp('refunded_at')->nullable();
            $table->unsignedBigInteger('refunded_by')->nullable(); // Admin who processed refund
            $table->text('refund_reason')->nullable();
            
            // Processing Timestamps
            $table->timestamp('initiated_at')->nullable();
            $table->timestamp('processed_at')->nullable()->index();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            
            // Settlement Information (for merchant transactions)
            $table->unsignedBigInteger('settlement_id')->nullable()->index();
            $table->boolean('is_settled')->default(false)->index();
            $table->timestamp('settled_at')->nullable();
            
            // Security & Verification
            $table->string('signature', 255)->nullable(); // API request signature
            $table->ipAddress('ip_address')->nullable();
            $table->string('user_agent')->nullable();
            $table->string('location')->nullable(); // GPS coordinates if available
            
            // Risk & Fraud Detection
            $table->enum('risk_level', [
                'low',
                'medium',
                'high',
                'blocked'
            ])->default('low')->index();
            $table->decimal('risk_score', 5, 2)->nullable(); // 0-100
            $table->boolean('requires_review')->default(false)->index();
            $table->timestamp('reviewed_at')->nullable();
            $table->unsignedBigInteger('reviewed_by')->nullable();
            
            // Notification Status
            $table->boolean('notification_sent')->default(false);
            $table->timestamp('notification_sent_at')->nullable();
            
            // Webhook Status
            $table->boolean('webhook_sent')->default(false);
            $table->integer('webhook_attempts')->default(0);
            $table->timestamp('webhook_sent_at')->nullable();
            
            // Reconciliation
            $table->boolean('is_reconciled')->default(false);
            $table->timestamp('reconciled_at')->nullable();
            $table->string('reconciliation_batch', 50)->nullable();
            
            // Timestamps
            $table->timestamps();
            $table->softDeletes();
            
            // Comprehensive Indexes for Performance
            $table->index(['from_wallet_id', 'status']);
            $table->index(['to_wallet_id', 'status']);
            $table->index(['merchant_id', 'status']);
            $table->index(['type', 'status']);
            $table->index(['status', 'created_at']);
            $table->index(['settlement_id', 'is_settled']);
            $table->index(['requires_review', 'risk_level']);
            $table->index('created_at');
            
            // Foreign Keys
            $table->foreign('from_wallet_id')
                  ->references('id')
                  ->on('wallets')
                  ->onDelete('set null');
                  
            $table->foreign('to_wallet_id')
                  ->references('id')
                  ->on('wallets')
                  ->onDelete('set null');
                  
            $table->foreign('from_user_id')
                  ->references('id')
                  ->on('users')
                  ->onDelete('set null');
                  
            $table->foreign('to_user_id')
                  ->references('id')
                  ->on('users')
                  ->onDelete('set null');
                  
            $table->foreign('card_id')
                  ->references('id')
                  ->on('cards')
                  ->onDelete('set null');
                  
            $table->foreign('parent_transaction_id')
                  ->references('id')
                  ->on('transactions')
                  ->onDelete('set null');
        });
        
        DB::statement("ALTER TABLE transactions COMMENT 'Comprehensive transaction records for all payment activities'");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
