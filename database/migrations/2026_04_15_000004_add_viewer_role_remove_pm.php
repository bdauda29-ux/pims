<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $abilities = [
            'personnel.view',
            'personnel.register',
            'personnel.edit',
            'personnel.import',
            'personnel.export',
            'formations.manage',
            'directorates.manage',
            'offices.manage',
            'custom_fields.manage',
            'roles.manage',
            'audit.view',
            'management.view',
        ];

        $rows = [];
        foreach ($abilities as $ability) {
            $rows[] = [
                'role' => 'Viewer',
                'ability' => $ability,
                'allowed' => $ability === 'personnel.view',
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        DB::table('role_permissions')->insertOrIgnore($rows);

        DB::table('role_permissions')->where('role', 'Provost Marshal (PM)')->delete();

        DB::table('users')
            ->where('role', 'Provost Marshal (PM)')
            ->update([
                'role' => 'Viewer',
                'updated_at' => now(),
            ]);
    }

    public function down(): void
    {
        $abilities = [
            'personnel.view',
            'personnel.register',
            'personnel.edit',
            'personnel.import',
            'personnel.export',
            'formations.manage',
            'directorates.manage',
            'offices.manage',
            'custom_fields.manage',
            'roles.manage',
            'audit.view',
            'management.view',
        ];

        $rows = [];
        foreach ($abilities as $ability) {
            $rows[] = [
                'role' => 'Provost Marshal (PM)',
                'ability' => $ability,
                'allowed' => in_array($ability, ['personnel.view', 'personnel.export'], true),
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        DB::table('role_permissions')->insertOrIgnore($rows);

        DB::table('users')
            ->where('role', 'Viewer')
            ->where('name', 'Provost Marshal (PM)')
            ->update([
                'role' => 'Provost Marshal (PM)',
                'updated_at' => now(),
            ]);

        DB::table('role_permissions')->where('role', 'Viewer')->delete();
    }
};
