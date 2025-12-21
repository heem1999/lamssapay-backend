<?php

/**
 * NFCPay Database Migration
 * 
 * Creates the fees table for configuring transaction fees.
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
        Schema::create('fees', function (Blueprint $table) {
            $table->id();
            
            $table->string('name', 100);
            $table->string('code', 50)->unique(); // e.g., 'standard_transfer', 'instant_payout'
            
            $table->enum('type', ['fixed', 'percentage', 'combination'])->default('percentage');
            $table->decimal('fixed_amount', 10, 2)->default(0.00);
            $table->decimal('percentage_rate', 5, 2)->default(0.00); // e.g., 2.50 for 2.5%
            
            $table->string('currency', 3)->default('USD');
            
            // Applicability
            $table->enum('applies_to', ['transaction', 'withdrawal', 'deposit', 'card_issuance'])->default('transaction');
            $table->unsignedBigInteger('partner_id')->nullable(); // Specific fee for a partner
            $table->unsignedBigInteger('merchant_id')->nullable(); // Specific fee for a merchant
            
            $table->boolean('is_active')->default(true);
            
            $table->timestamps();
            
            $table->foreign('partner_id')->references('id')->on('partners')->onDelete('cascade');
            $table->foreign('merchant_id')->references('id')->on('merchants')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('fees');
    }
};
