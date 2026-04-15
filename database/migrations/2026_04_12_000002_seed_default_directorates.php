<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('directorates')->insertOrIgnore([
            ['name' => 'Human Resource Management (HRM)'],
            ['name' => 'Finance and Accounts (F/A)'],
            ['name' => 'Planning, Research and Statistics (PRS)'],
            ['name' => 'Passport and Other Travel Documents (POTD)'],
            ['name' => 'Visa and Residency (V/R)'],
            ['name' => 'Migration Management'],
            ['name' => 'Border Management'],
            ['name' => 'Investigation and Compliance (I/C)'],
            ['name' => 'ICT and Cyber Security (ICT)'],
            ['name' => 'Works and Logistics (W/S)'],
        ]);
    }

    public function down(): void
    {
        DB::table('directorates')
            ->whereIn('name', [
                'Human Resource Management (HRM)',
                'Finance and Accounts (F/A)',
                'Planning, Research and Statistics (PRS)',
                'Passport and Other Travel Documents (POTD)',
                'Visa and Residency (V/R)',
                'Migration Management',
                'Border Management',
                'Investigation and Compliance (I/C)',
                'ICT and Cyber Security (ICT)',
                'Works and Logistics (W/S)',
            ])
            ->delete();
    }
};
