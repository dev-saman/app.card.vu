<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('registration_sessions', function (Blueprint $table) {
            $table->id();

            // Unique token to track the registration session across steps
            $table->string('token')->unique();

            // Tracks which step the user last completed (1=type selected, 2=account details submitted, 3=otp verified)
            $table->tinyInteger('current_step')->default(1);

            // Step 1: registration type selection
            $table->enum('registration_type', ['working_professional', 'service_professional'])->nullable();

            // Step 2: account details
            $table->string('full_name')->nullable();
            $table->string('mobile_number', 20)->nullable();
            $table->string('country_code', 10)->nullable();

            // Step 3: OTP verification
            $table->string('otp', 6)->nullable();
            $table->timestamp('otp_expires_at')->nullable();
            $table->tinyInteger('otp_verified')->default(0);

            // Session expiry
            $table->timestamp('expires_at')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('registration_sessions');
    }
};
