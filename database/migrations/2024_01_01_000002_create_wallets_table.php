<?php

/**
 * NFCPay Database Migration
 * 
 * Creates the wallets table for storing user wallet information.
 * Each user has a primary wallet for managing balance and transactions.
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
     * Creates the wallets table which stores digital wallet information
     * for users and merchants. Features include:
     * - Multi-currency support
     * - Balance management
     * - Wallet locking mechanisms
     * - Transaction limits
     *
     * @return void
     */
    public function up(): void
    {
        Schema::create('wallets', function (Blueprint $table) {
            // Primary Key
            $table->id();
            
            // User Relationship
            $table->unsignedBigInteger('user_id')->unique(); // One wallet per user
            
            // Wallet Identifier (Public)
            $table->string('wallet_id', 50)->unique(); // e.g., WLT_ABC123XYZ
            
            // Balance Information
            $table->decimal('balance', 15, 2)->default(0.00);
            $table->decimal('available_balance', 15, 2)->default(0.00); // Excluding held amounts
            $table->decimal('held_balance', 15, 2)->default(0.00); // Pending transactions
            
            // Currency Configuration
            $table->string('currency', 3)->default('USD')->index();
            
            // Wallet Status
            $table->boolean('is_active')->default(true)->index();
            $table->boolean('is_locked')->default(false)->index();
            $table->text('locked_reason')->nullable();
            $table->timestamp('locked_at')->nullable();
            $table->unsignedBigInteger('locked_by')->nullable(); // Admin who locked it
            
            // Transaction Limits
            $table->decimal('daily_limit', 15, 2)->nullable();
            $table->decimal('daily_spent', 15, 2)->default(0.00);
            $table->date('daily_reset_date')->nullable();
            
            $table->decimal('monthly_limit', 15, 2)->nullable();
            $table->decimal('monthly_spent', 15, 2)->default(0.00);
            $table->date('monthly_reset_date')->nullable();
            
            $table->decimal('transaction_limit', 15, 2)->nullable(); // Per transaction
            
            // Wallet Type
            $table->enum('wallet_type', [
                'user',      // Regular user wallet
                'merchant',  // Merchant wallet
                'system'     // System wallet (fees, etc.)
            ])->default('user')->index();
            
            // Settlement Information (for merchants)
            $table->decimal('pending_settlement', 15, 2)->default(0.00);
            $table->timestamp('last_settlement_at')->nullable();
            
            // Security
            $table->string('pin_hash')->nullable(); // Encrypted wallet PIN
            $table->integer('pin_attempts')->default(0);
            $table->timestamp('pin_locked_until')->nullable();
            
            // Statistics
            $table->unsignedBigInteger('total_transactions')->default(0);
            $table->decimal('total_received', 15, 2)->default(0.00);
            $table->decimal('total_sent', 15, 2)->default(0.00);
            
            // Metadata
            $table->json('metadata')->nullable();
            
            // Timestamps
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes
            $table->index(['is_active', 'is_locked']);
            $table->index('created_at');
            
            // Foreign Keys
            $table->foreign('user_id')
                  ->references('id')
                  ->on('users')
                  ->onDelete('cascade');
                  
            $table->foreign('locked_by')
                  ->references('id')
                  ->on('users')
                  ->onDelete('set null');
        });
        
        DB::statement("ALTER TABLE wallets COMMENT 'Digital wallets for users and merchants with balance management'");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('wallets');
    }
};
