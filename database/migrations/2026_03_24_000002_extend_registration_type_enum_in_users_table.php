<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Extends the registration_type enum to include new professional card types.
     */
    public function up(): void
    {
        DB::statement("ALTER TABLE users MODIFY COLUMN registration_type ENUM('brand', 'professional', 'working_professional', 'service_professional') NOT NULL DEFAULT 'brand'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("ALTER TABLE users MODIFY COLUMN registration_type ENUM('brand', 'professional') NOT NULL DEFAULT 'brand'");
    }
};
