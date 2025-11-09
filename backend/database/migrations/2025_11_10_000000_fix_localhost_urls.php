<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('users')
            ->where('profile_image', 'like', 'http://localhost:8000%')
            ->update([
                'profile_image' => DB::raw("REPLACE(profile_image, 'http://localhost:8000', 'https://jobai-production-0b94.up.railway.app')")
            ]);
    }

    public function down(): void
    {
        //
    }
};
