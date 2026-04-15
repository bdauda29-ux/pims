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

        $defaults = [
            'Super Admin' => [
                'formations.manage' => true,
                'directorates.manage' => true,
            ],
            'Main Admin' => array_fill_keys($abilities, true),
            'Formation Admin' => [
                'personnel.view' => true,
                'personnel.register' => true,
                'personnel.export' => true,
                'offices.manage' => true,
            ],
            'Office Admin' => [
                'personnel.view' => true,
                'personnel.export' => true,
            ],
            'DCG' => [
                'personnel.view' => true,
                'personnel.export' => true,
            ],
            'Principal Staff Officer (PSO)' => [
                'personnel.view' => true,
                'personnel.export' => true,
            ],
            'Provost Marshal (PM)' => [
                'personnel.view' => true,
                'personnel.export' => true,
            ],
        ];

        $rows = [];
        foreach ($defaults as $role => $map) {
            foreach ($abilities as $ability) {
                $rows[] = [
                    'role' => $role,
                    'ability' => $ability,
                    'allowed' => (bool) ($map[$ability] ?? false),
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
        }

        DB::table('role_permissions')->insertOrIgnore($rows);
    }

    public function down(): void
    {
        DB::table('role_permissions')->truncate();
    }
};
