<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Modify the ENUM column to include 'cancelled'
        DB::statement("ALTER TABLE merchant_requests MODIFY COLUMN status ENUM('pending', 'under_review', 'approved', 'rejected', 'more_info_required', 'cancelled') DEFAULT 'pending'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert the ENUM column to the original values (WARNING: this might fail if there are 'cancelled' records)
        // We generally don't want to lose data, so we might just leave it or map 'cancelled' to 'rejected' before reverting.
        // For now, we will just revert the definition.
        DB::statement("UPDATE merchant_requests SET status = 'rejected' WHERE status = 'cancelled'");
        DB::statement("ALTER TABLE merchant_requests MODIFY COLUMN status ENUM('pending', 'under_review', 'approved', 'rejected', 'more_info_required') DEFAULT 'pending'");
    }
};
