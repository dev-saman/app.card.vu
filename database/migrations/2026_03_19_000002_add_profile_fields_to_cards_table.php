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
        Schema::table('cards', function (Blueprint $table) {
            // Foreign keys for template and category
            $table->foreignId('template_id')->nullable()->constrained('templates')->onDelete('set null')->after('workspace_id');
            $table->foreignId('category_id')->nullable()->constrained('categories')->onDelete('set null')->after('template_id');

            // Custom card URL / username slug (e.g. your-card-url.card.vu)
            $table->string('card_url')->nullable()->unique()->after('name');

            // Professional title or headline shown on the card
            $table->string('headline')->nullable()->after('card_url');

            // Specializations / category tags (JSON array)
            $table->json('specializations')->nullable()->after('headline');

            // Feature highlights / badges shown on the card (max 3, JSON array)
            $table->json('highlights')->nullable()->after('specializations');

            // Google Business Profile URL or Place ID for reviews sync
            $table->string('google_business_profile')->nullable()->after('highlights');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cards', function (Blueprint $table) {
            $table->dropColumn([
                'template_id',
                'category_id',
                'card_url',
                'headline',
                'specializations',
                'highlights',
                'google_business_profile',
            ]);
        });
    }
};
