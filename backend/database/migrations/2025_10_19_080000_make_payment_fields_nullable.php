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
        if (DB::getDriverName() === 'sqlite') {
            return;
        }

        // Check if table exists
        if (!DB::getSchemaBuilder()->hasTable('payments')) {
            return;
        }

        if (DB::getDriverName() === 'pgsql') {
            DB::statement("ALTER TABLE payments ALTER COLUMN application_id DROP NOT NULL");
            DB::statement("ALTER TABLE payments ALTER COLUMN jobseeker_id DROP NOT NULL");
        } else {
            DB::statement("ALTER TABLE `payments` MODIFY `application_id` BIGINT UNSIGNED NULL");
            DB::statement("ALTER TABLE `payments` MODIFY `jobseeker_id` BIGINT UNSIGNED NULL");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            return;
        }

        if (DB::getDriverName() === 'pgsql') {
            DB::statement("ALTER TABLE payments ALTER COLUMN application_id SET NOT NULL");
            DB::statement("ALTER TABLE payments ALTER COLUMN jobseeker_id SET NOT NULL");
        } else {
            DB::statement("ALTER TABLE `payments` MODIFY `application_id` BIGINT UNSIGNED NOT NULL");
            DB::statement("ALTER TABLE `payments` MODIFY `jobseeker_id` BIGINT UNSIGNED NOT NULL");
        }
    }
};
