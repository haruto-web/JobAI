<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (DB::getDriverName() === 'pgsql') {
            DB::statement("ALTER TABLE job_listings DROP CONSTRAINT IF EXISTS job_listings_status_check");
            DB::statement("ALTER TABLE job_listings ALTER COLUMN status TYPE VARCHAR(255)");
            DB::statement("ALTER TABLE job_listings ADD CONSTRAINT job_listings_status_check CHECK (status IN ('draft', 'pending_approval', 'approved', 'rejected', 'archived'))");
        } else {
            Schema::table('job_listings', function (Blueprint $table) {
                $table->enum('status', ['draft', 'pending_approval', 'approved', 'rejected', 'archived'])->default('draft')->change();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (DB::getDriverName() === 'pgsql') {
            DB::statement("ALTER TABLE job_listings DROP CONSTRAINT IF EXISTS job_listings_status_check");
            DB::statement("ALTER TABLE job_listings ADD CONSTRAINT job_listings_status_check CHECK (status IN ('draft', 'published', 'archived'))");
        } else {
            Schema::table('job_listings', function (Blueprint $table) {
                $table->enum('status', ['draft', 'published', 'archived'])->default('draft')->change();
            });
        }
    }
};
