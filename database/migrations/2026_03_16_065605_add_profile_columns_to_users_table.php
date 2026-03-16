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
        Schema::table('users', function (Blueprint $table) {
            // Account type: 'brand' or 'professional'
            $table->enum('registration_type', ['brand', 'professional'])
                  ->default('brand')
                  ->after('email');

            // Account status: 'active' or 'inactive'
            $table->enum('status', ['active', 'inactive'])
                  ->default('active')
                  ->after('registration_type');

            // User's IANA timezone string e.g. 'Asia/Kolkata'
            $table->string('timezone', 100)
                  ->nullable()
                  ->after('status');

            // Country name or ISO 3166-1 alpha-2 code e.g. 'IN'
            $table->string('country', 100)
                  ->nullable()
                  ->after('timezone');

            // Full URL to the user's profile picture
            $table->string('profile_picture')
                  ->nullable()
                  ->after('country');

            // Mobile number stored with country code e.g. '+919004583919'
            $table->string('mobile_number', 20)
                  ->nullable()
                  ->after('profile_picture');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'registration_type',
                'status',
                'timezone',
                'country',
                'profile_picture',
                'mobile_number',
            ]);
        });
    }
};
