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
        Schema::create('card_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('card_id')->constrained('cards')->onDelete('cascade');

            // Contact Information
            $table->string('phone_number')->nullable();
            $table->string('email_address')->nullable();
            $table->string('whatsapp_number')->nullable();
            $table->string('telegram')->nullable();
            $table->string('website')->nullable();
            $table->text('address')->nullable();

            // Working Hours (stored as JSON)
            // Format: { "type": "all_days|weekdays|custom|by_appointment", "hours": { "monday": { "enabled": true, "from": "09:00", "to": "17:00" }, ... } }
            $table->json('working_hours')->nullable();

            // Inquiry Email Addresses (stored as JSON array)
            // Format: ["email1@example.com", "email2@example.com"]
            $table->json('inquiry_emails')->nullable();

            // Social Media Links
            $table->string('linkedin')->nullable();
            $table->string('instagram')->nullable();
            $table->string('youtube')->nullable();
            $table->string('facebook')->nullable();
            $table->string('x_twitter')->nullable();
            $table->string('github')->nullable();
            $table->string('behance')->nullable();
            $table->string('dribbble')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('card_details');
    }
};
