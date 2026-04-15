<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('role_permissions')
            ->whereIn('role', ['DCG', 'Principal Staff Officer (PSO)'])
            ->where('ability', 'offices.manage')
            ->update([
                'allowed' => true,
                'updated_at' => now(),
            ]);
    }

    public function down(): void
    {
        DB::table('role_permissions')
            ->whereIn('role', ['DCG', 'Principal Staff Officer (PSO)'])
            ->where('ability', 'offices.manage')
            ->update([
                'allowed' => false,
                'updated_at' => now(),
            ]);
    }
};

