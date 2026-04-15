<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $map = [
            'Human Resource Management (HRM)' => 'HRM',
            'Finance and Accounts (F/A)' => 'FA',
            'Planning, Research and Statistics (PRS)' => 'PRS',
            'Passport and Other Travel Documents (POTD)' => 'POTD',
            'Visa and Residency (V/R)' => 'VR',
            'Migration Management (MIG)' => 'MIG',
            'Border Management (BM)' => 'BM',
            'Investigation and Compliance (I/C)' => 'IC',
            'ICT and Cyber Security (ICT)' => 'ICT',
            'Works and Logistics (W/S)' => 'WL',
        ];

        foreach ($map as $name => $code) {
            DB::table('directorates')->where('name', $name)->update(['code' => $code]);
        }
    }

    public function down(): void
    {
        DB::table('directorates')->update(['code' => null]);
    }
};
