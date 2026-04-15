<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('directorates')
            ->where('name', 'Migration Management')
            ->update(['name' => 'Migration Management (MIG)']);

        DB::table('directorates')
            ->where('name', 'Border Management')
            ->update(['name' => 'Border Management (BM)']);
    }

    public function down(): void
    {
        DB::table('directorates')
            ->where('name', 'Migration Management (MIG)')
            ->update(['name' => 'Migration Management']);

        DB::table('directorates')
            ->where('name', 'Border Management (BM)')
            ->update(['name' => 'Border Management']);
    }
};
