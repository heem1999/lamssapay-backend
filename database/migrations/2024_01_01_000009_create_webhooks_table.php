<?php

/**
 * NFCPay Database Migration
 * 
 * Creates the webhooks table for storing webhook configurations and delivery logs.
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
        Schema::create('webhooks', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('partner_id')->nullable()->index(); // If associated with a partner
            $table->unsignedBigInteger('merchant_id')->nullable()->index(); // If associated with a merchant
            
            $table->string('url', 255);
            $table->string('secret', 255)->nullable(); // For signature verification
            $table->json('events'); // Array of event names to listen for
            
            $table->boolean('is_active')->default(true);
            $table->integer('failure_count')->default(0);
            $table->timestamp('last_triggered_at')->nullable();
            
            $table->timestamps();
            
            // Polymorphic or specific relations? Keeping it simple with nullable FKs for now
            $table->foreign('partner_id')->references('id')->on('partners')->onDelete('cascade');
            $table->foreign('merchant_id')->references('id')->on('merchants')->onDelete('cascade');
        });

        Schema::create('webhook_deliveries', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('webhook_id')->index();
            
            $table->string('event_type', 100);
            $table->uuid('event_id')->nullable();
            $table->json('payload');
            
            $table->integer('status_code')->nullable();
            $table->text('response_body')->nullable();
            $table->boolean('success')->default(false);
            
            $table->integer('attempt')->default(1);
            $table->timestamp('sent_at')->useCurrent();
            $table->timestamp('next_retry_at')->nullable();
            
            $table->foreign('webhook_id')->references('id')->on('webhooks')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('webhook_deliveries');
        Schema::dropIfExists('webhooks');
    }
};
