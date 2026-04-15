<?php

namespace App\Console\Commands;

use App\Models\Formation;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;

class ResetAdminCredentials extends Command
{
    protected $signature = 'app:reset-admin-credentials';

    protected $description = 'Create/update default Super Admin and Main Admin accounts (sadmin/NIS001) and reset their passwords to match their login.';

    public function handle(): int
    {
        $formation = Formation::firstOrCreate(['name' => 'Service Headquarters [SHQ]']);

        User::updateOrCreate(
            ['email' => 'superadmin@example.com'],
            [
                'nis_no' => 'sadmin',
                'name' => 'Super Admin',
                'email' => 'superadmin@example.com',
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
                'email' => 'mainadmin@example.com',
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

        $this->info('Admin credentials reset.');
        $this->line('Super Admin: sadmin / sadmin');
        $this->line('Main Admin:  NIS001 / NIS001');

        return self::SUCCESS;
    }
}
