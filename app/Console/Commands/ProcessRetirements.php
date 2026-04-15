<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

#[Signature('app:process-retirements')]
#[Description('Automatically retires personnel by age (60) or years of service (35)')]
class ProcessRetirements extends Command
{
    /**
     * Execute the console command.
     */
    public function handle()
    {
        $today = Carbon::today();
        $retired = 0;

        User::query()
            ->where('employment_status', 'Active')
            ->where(function ($q) {
                $q->whereNotNull('date_of_birth')
                    ->orWhereNotNull('date_of_first_appointment');
            })
            ->chunkById(500, function ($users) use ($today, &$retired) {
                foreach ($users as $user) {
                    $retirementDate = $user->retirementDate();
                    if (! $retirementDate) {
                        continue;
                    }

                    $user->update([
                        'date_of_retirement' => $retirementDate->toDateString(),
                    ]);

                    if ($retirementDate->isSameDay($today) || $retirementDate->lessThan($today)) {
                        $user->update([
                            'employment_status' => 'Inactive',
                            'retired_at' => $today->toDateString(),
                            'retirement_reason' => 'Auto Retirement',
                        ]);
                        $retired++;
                    }
                }
            });

        $this->info("Auto retirement processing complete. Retired {$retired} personnel.");

        return Command::SUCCESS;
    }
}
