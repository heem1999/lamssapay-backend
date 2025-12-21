<?php

/**
 * NFCPay Database Migration
 * 
 * Creates the support_tickets table for managing user inquiries and issues.
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
        Schema::create('support_tickets', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->index();
            
            $table->string('subject', 255);
            $table->text('message');
            
            $table->enum('status', ['open', 'in_progress', 'resolved', 'closed'])->default('open')->index();
            $table->enum('priority', ['low', 'medium', 'high', 'critical'])->default('medium');
            $table->string('category', 50)->nullable(); // e.g., 'payment_issue', 'account_access'
            
            $table->unsignedBigInteger('assigned_to')->nullable(); // Admin ID
            
            $table->timestamps();
            $table->softDeletes();
            
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('assigned_to')->references('id')->on('users')->onDelete('set null');
        });

        Schema::create('support_ticket_messages', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('ticket_id')->index();
            $table->unsignedBigInteger('user_id')->index(); // Sender (User or Admin)
            
            $table->text('message');
            $table->json('attachments')->nullable();
            
            $table->boolean('is_admin_reply')->default(false);
            
            $table->timestamps();
            
            $table->foreign('ticket_id')->references('id')->on('support_tickets')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('support_ticket_messages');
        Schema::dropIfExists('support_tickets');
    }
};
