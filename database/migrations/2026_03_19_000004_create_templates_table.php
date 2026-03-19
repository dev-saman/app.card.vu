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
        Schema::create('templates', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('thumbnail')->nullable();
            $table->string('layout')->nullable();
            $table->json('colors')->nullable();
            $table->json('fonts')->nullable();
            $table->tinyInteger('is_active')->default(1);
            $table->timestamps();
        });

        // Now that templates table exists, add the foreign key constraint on cards.template_id
        Schema::table('cards', function (Blueprint $table) {
            $table->foreign('template_id')->references('id')->on('templates')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop the foreign key constraint before dropping the templates table
        Schema::table('cards', function (Blueprint $table) {
            $table->dropForeign(['template_id']);
        });

        Schema::dropIfExists('templates');
    }
};
