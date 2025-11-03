<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Use raw statements to avoid requiring doctrine/dbal for change()
        // Skip raw MySQL ALTER TABLE MODIFY statements on SQLite (in tests)
        if (DB::getDriverName() === 'sqlite') {
            return;
        }

        DB::statement("ALTER TABLE `payments` MODIFY `application_id` BIGINT UNSIGNED NULL");
        DB::statement("ALTER TABLE `payments` MODIFY `jobseeker_id` BIGINT UNSIGNED NULL");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            return;
        }

        DB::statement("ALTER TABLE `payments` MODIFY `application_id` BIGINT UNSIGNED NOT NULL");
        DB::statement("ALTER TABLE `payments` MODIFY `jobseeker_id` BIGINT UNSIGNED NOT NULL");
    }
};
