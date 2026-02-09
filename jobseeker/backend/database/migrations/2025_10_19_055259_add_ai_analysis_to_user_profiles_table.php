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
        Schema::table('user_profiles', function (Blueprint $table) {
            $table->json('ai_analysis')->nullable(); // Store comprehensive AI analysis
            $table->text('extracted_experience')->nullable(); // Years of experience
            $table->text('extracted_education')->nullable(); // Education details
            $table->text('extracted_certifications')->nullable(); // Certifications
            $table->text('extracted_languages')->nullable(); // Languages
            $table->text('resume_summary')->nullable(); // AI-generated summary
            $table->timestamp('last_ai_analysis')->nullable(); // When AI analysis was last run
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('user_profiles', function (Blueprint $table) {
            $table->dropColumn([
                'ai_analysis',
                'extracted_experience',
                'extracted_education',
                'extracted_certifications',
                'extracted_languages',
                'resume_summary',
                'last_ai_analysis'
            ]);
        });
    }
};
