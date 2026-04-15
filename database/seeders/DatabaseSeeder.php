<?php

namespace Database\Seeders;

use App\Models\Formation;
use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call(StateLgaSeeder::class);

        $formationNames = [
            'Service Headquarters [SHQ]',
            'Zonal Command',
            'State Command',
            'Border Command',
            'Special Command',
            'Airport',
            'Marine Command',
            'Passport Command',
        ];

        foreach ($formationNames as $name) {
            Formation::firstOrCreate(['name' => $name]);
        }

        $formation = Formation::firstOrCreate(['name' => 'Service Headquarters [SHQ]']);

        User::updateOrCreate(
            ['email' => 'superadmin@example.com'],
            [
                'nis_no' => 'sadmin',
                'name' => 'Super Admin',
                'password' => Hash::make('sadmin'),
                'role' => 'Super Admin',
                'formation_id' => $formation->id,
                'surname' => 'Admin',
                'first_name' => 'Super',
                'email_verified_at' => now(),
                'must_change_password' => false,
                'employment_status' => 'Active',
            ],
        );

        User::updateOrCreate(
            ['email' => 'mainadmin@example.com'],
            [
                'nis_no' => 'NIS001',
                'name' => 'Main Admin',
                'password' => Hash::make('NIS001'),
                'role' => 'Main Admin',
                'formation_id' => $formation->id,
                'surname' => 'Admin',
                'first_name' => 'Main',
                'email_verified_at' => now(),
                'must_change_password' => false,
                'employment_status' => 'Active',
            ],
        );
    }
}
