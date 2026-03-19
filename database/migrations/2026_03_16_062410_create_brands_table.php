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
        Schema::create('brands', function (Blueprint $table) {
            $table->id();

            // Foreign key — owner user
            $table->foreignId('user_id')
                  ->constrained('users')
                  ->onDelete('cascade');

            // Brand identity
            $table->string('brand_name');
            $table->string('url')->unique();          // slug e.g. 'riders-rally' → card.vu/riders-rally
            $table->string('category', 100)->nullable();

            // Operational details
            $table->string('owner_name');
            $table->string('country', 100)->nullable();
            $table->string('timezone', 100)->nullable(); // IANA e.g. 'Asia/Kolkata'
            $table->string('currency', 10)->nullable();  // ISO 4217 e.g. 'INR', 'USD'

            // Status: 1 = active, 0 = inactive
            $table->tinyInteger('status')->default(1);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('brands');
    }
};
