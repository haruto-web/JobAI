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
            DB::statement("ALTER TABLE applications DROP CONSTRAINT IF EXISTS applications_status_check");
            DB::statement("ALTER TABLE applications ALTER COLUMN status TYPE VARCHAR(255)");
            DB::statement("ALTER TABLE applications ADD CONSTRAINT applications_status_check CHECK (status IN ('pending', 'accepted', 'rejected', 'cancelled'))");
        } else {
            Schema::table('applications', function (Blueprint $table) {
                $table->enum('status', ['pending', 'accepted', 'rejected', 'cancelled'])->default('pending')->change();
            });
        }
        
        Schema::table('applications', function (Blueprint $table) {
            $table->text('cancel_reason')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (DB::getDriverName() === 'pgsql') {
            DB::statement("ALTER TABLE applications DROP CONSTRAINT IF EXISTS applications_status_check");
            DB::statement("ALTER TABLE applications ADD CONSTRAINT applications_status_check CHECK (status IN ('pending', 'accepted', 'rejected'))");
        } else {
            Schema::table('applications', function (Blueprint $table) {
                $table->enum('status', ['pending', 'accepted', 'rejected'])->default('pending')->change();
            });
        }
        
        Schema::table('applications', function (Blueprint $table) {
            $table->dropColumn('cancel_reason');
        });
    }
};
