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
        Schema::create('login_otps', function (Blueprint $table) {
            $table->id();

            // Mobile number with country code (e.g. +919876543210)
            $table->string('mobile_number', 20)->index();

            // 6-digit OTP code
            $table->string('otp', 6);

            // OTP expiry timestamp (10 minutes from creation)
            $table->timestamp('expires_at');

            // Whether this OTP has been used
            $table->tinyInteger('is_used')->default(0);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('login_otps');
    }
};
