<?php

namespace Database\Seeders;

use App\Models\Lga;
use App\Models\State;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;

class StateLgaSeeder extends Seeder
{
    public function run(): void
    {
        $json = File::get(database_path('data/nigeria_states_lgas.json'));
        $statesData = json_decode($json, true);

        foreach ($statesData as $stateName => $lgas) {
            $state = State::firstOrCreate(['name' => $stateName]);
            foreach ($lgas as $lgaName) {
                Lga::firstOrCreate(['name' => $lgaName, 'state_id' => $state->id]);
            }
        }
    }
}
