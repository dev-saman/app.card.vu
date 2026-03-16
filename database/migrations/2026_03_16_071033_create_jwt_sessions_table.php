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
        Schema::create('jwt_sessions', function (Blueprint $table) {
            $table->id();

            // The user this session belongs to
            $table->foreignId('user_id')
                  ->constrained('users')
                  ->onDelete('cascade');

            // The full JWT token string (stored for revocation lookup)
            $table->text('token');

            // SHA-256 hash of the token for fast indexed lookups
            $table->string('token_hash', 64)->unique();

            // Device / client info
            $table->string('device_name')->nullable();   // e.g. 'iPhone 15', 'Chrome on Windows'
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();

            // Token lifecycle
            $table->timestamp('expires_at')->nullable();  // when the JWT expires
            $table->timestamp('last_used_at')->nullable(); // updated on each authenticated request
            $table->timestamp('revoked_at')->nullable();   // set when token is explicitly logged out

            // Is this session currently valid?
            // 1 = active, 0 = revoked/expired
            $table->tinyInteger('is_active')->default(1);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('jwt_sessions');
    }
};
