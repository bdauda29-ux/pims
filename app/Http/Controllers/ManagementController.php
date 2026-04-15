<?php

namespace App\Http\Controllers;

use App\Models\Directorate;
use App\Models\Formation;
use App\Models\User;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;

class ManagementController extends Controller
{
    private const STANDALONE_ADMIN_ROLES = [
        'Super Admin',
        'Main Admin',
        'Formation Admin',
        'DCG',
        'Principal Staff Officer (PSO)',
        'Viewer',
    ];

    public function index()
    {
        $shq = Formation::query()
            ->where('code', 'SHQ')
            ->orWhere('name', 'like', '%service headquarters%')
            ->orWhere('name', 'like', '%shq%')
            ->first();

        $directorates = Directorate::orderBy('name')->get();
        $formations = Formation::query()->get(['id', 'name', 'code', 'type', 'parent_id']);
        $childrenByParent = $formations->groupBy('parent_id');
        $zonalRoots = $formations->where('type', 'Zonal Command')->sortBy('name')->values();

        $formationRows = [];
        $visited = [];
        $walk = function ($formationId, int $depth) use (&$walk, &$formationRows, &$visited, $formations, $childrenByParent) {
            if (isset($visited[$formationId])) {
                return;
            }
            $visited[$formationId] = true;

            $formation = $formations->firstWhere('id', $formationId);
            if (! $formation) {
                return;
            }

            $formationRows[] = ['formation' => $formation, 'depth' => $depth];

            $children = $childrenByParent->get($formationId, collect());
            foreach ($children->sortBy('name')->values() as $child) {
                $walk($child->id, $depth + 1);
            }
        };

        foreach ($zonalRoots as $root) {
            $walk($root->id, 0);
        }

        $formationCounts = User::query()
            ->where('employment_status', 'Active')
            ->whereNotNull('formation_id')
            ->whereNotIn('role', self::STANDALONE_ADMIN_ROLES)
            ->selectRaw('formation_id, count(*) as c')
            ->groupBy('formation_id')
            ->pluck('c', 'formation_id')
            ->all();

        $directorateCounts = User::query()
            ->where('employment_status', 'Active')
            ->whereNotNull('directorate_id')
            ->whereNotIn('role', self::STANDALONE_ADMIN_ROLES)
            ->selectRaw('directorate_id, count(*) as c')
            ->groupBy('directorate_id')
            ->pluck('c', 'directorate_id')
            ->all();

        $shqTotalDirectoratePersonnel = array_sum(array_map('intval', $directorateCounts));

        return view('management.index', compact('shq', 'directorates', 'formationRows', 'formationCounts', 'directorateCounts', 'shqTotalDirectoratePersonnel'));
    }

    public function formationAdmins(Formation $formation)
    {
        $admins = User::query()
            ->where('employment_status', 'Active')
            ->where('role', 'Formation Admin')
            ->where('formation_id', $formation->id)
            ->orderBy('surname')
            ->get();

        return view('management.formation-admins', compact('formation', 'admins'));
    }

    public function directorateAdmins(Directorate $directorate)
    {
        $admins = User::query()
            ->where('employment_status', 'Active')
            ->where('role', 'DCG')
            ->where('directorate_id', $directorate->id)
            ->orderBy('surname')
            ->get();

        return view('management.directorate-admins', compact('directorate', 'admins'));
    }

    public function resetPassword(User $user)
    {
        if ($user->isMainAdmin() || $user->isSuperAdmin()) {
            abort(403);
        }

        $user->update([
            'password' => Hash::make((string) $user->nis_no),
            'must_change_password' => true,
        ]);

        return back()->with('success', 'Password reset successfully.');
    }

    public function deleteAdmin(User $user)
    {
        if ($user->isMainAdmin() || $user->isSuperAdmin()) {
            abort(403);
        }

        $user->delete();

        return back()->with('success', 'Admin deleted successfully.');
    }

    public function standaloneRoles()
    {
        $pso = User::query()->where('role', 'Principal Staff Officer (PSO)')->where('employment_status', 'Active')->first();
        $viewer = User::query()->where('role', 'Viewer')->where('employment_status', 'Active')->first();

        return view('management.standalone-roles', compact('pso', 'viewer'));
    }

    public function formationRankChart(Formation $formation)
    {
        $counts = User::query()
            ->where('employment_status', 'Active')
            ->where('formation_id', $formation->id)
            ->whereNotNull('rank_code')
            ->where('rank_code', '<>', '')
            ->selectRaw('rank_code, count(*) as c')
            ->groupBy('rank_code')
            ->orderBy('rank_code')
            ->pluck('c', 'rank_code')
            ->all();

        $rankOrder = collect(json_decode(File::get(database_path('data/ranks.json')), true))
            ->map(fn ($r) => $r['code'])
            ->values()
            ->all();

        $labels = array_keys($counts);
        usort($labels, function ($a, $b) use ($rankOrder) {
            $ia = array_search($a, $rankOrder, true);
            $ib = array_search($b, $rankOrder, true);
            $ia = $ia === false ? PHP_INT_MAX : $ia;
            $ib = $ib === false ? PHP_INT_MAX : $ib;

            return $ia <=> $ib;
        });

        $values = array_map(fn ($k) => (int) ($counts[$k] ?? 0), $labels);

        return view('management.rank-chart', [
            'title' => $formation->name.' Rank Chart',
            'labels' => $labels,
            'values' => $values,
        ]);
    }

    public function directorateRankChart(Directorate $directorate)
    {
        $counts = User::query()
            ->where('employment_status', 'Active')
            ->where('directorate_id', $directorate->id)
            ->whereNotNull('rank_code')
            ->where('rank_code', '<>', '')
            ->selectRaw('rank_code, count(*) as c')
            ->groupBy('rank_code')
            ->orderBy('rank_code')
            ->pluck('c', 'rank_code')
            ->all();

        $rankOrder = collect(json_decode(File::get(database_path('data/ranks.json')), true))
            ->map(fn ($r) => $r['code'])
            ->values()
            ->all();

        $labels = array_keys($counts);
        usort($labels, function ($a, $b) use ($rankOrder) {
            $ia = array_search($a, $rankOrder, true);
            $ib = array_search($b, $rankOrder, true);
            $ia = $ia === false ? PHP_INT_MAX : $ia;
            $ib = $ib === false ? PHP_INT_MAX : $ib;

            return $ia <=> $ib;
        });

        $values = array_map(fn ($k) => (int) ($counts[$k] ?? 0), $labels);

        return view('management.rank-chart', [
            'title' => $directorate->name.' Rank Chart',
            'labels' => $labels,
            'values' => $values,
        ]);
    }
}
