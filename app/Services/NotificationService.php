<?php

namespace App\Services;

use App\Models\User;
use App\Models\UserNotification;
use Illuminate\Support\Collection;

class NotificationService
{
    public function notifyUsers(iterable $users, string $title, ?string $body = null, ?int $actorId = null, ?string $type = null, ?array $data = null): void
    {
        foreach ($users as $u) {
            if (! $u instanceof User) {
                continue;
            }

            UserNotification::create([
                'user_id' => $u->id,
                'actor_id' => $actorId,
                'type' => $type,
                'title' => $title,
                'body' => $body,
                'data' => $data,
            ]);
        }
    }

    public function relatedAdminsForPersonnel(User $personnel): Collection
    {
        $query = User::query()->where('employment_status', 'Active');

        $mainAdmins = (clone $query)->where('role', 'Main Admin')->get();
        $pso = (clone $query)->where('role', 'Principal Staff Officer (PSO)')->get();

        $formationAdmins = collect();
        if ($personnel->formation_id) {
            $formationAdmins = (clone $query)->where('role', 'Formation Admin')->where('formation_id', $personnel->formation_id)->get();
        }

        $officeAdmins = collect();
        if ($personnel->office_id) {
            $officeAdmins = (clone $query)->where('role', 'Office Admin')->where('office_id', $personnel->office_id)->get();
        }

        $dcg = collect();
        if ($personnel->directorate_id) {
            $dcg = (clone $query)->where('role', 'DCG')->where('directorate_id', $personnel->directorate_id)->get();
        }

        return $mainAdmins
            ->concat($pso)
            ->concat($formationAdmins)
            ->concat($officeAdmins)
            ->concat($dcg)
            ->unique('id')
            ->values();
    }
}

