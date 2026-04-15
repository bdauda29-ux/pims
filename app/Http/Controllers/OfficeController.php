<?php

namespace App\Http\Controllers;

use App\Models\Directorate;
use App\Models\Formation;
use App\Models\Office;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class OfficeController extends Controller
{
    private function shqFormation(): ?Formation
    {
        return Formation::query()
            ->where(function ($q) {
                $q->whereRaw("upper(coalesce(code,'')) = 'SHQ'")
                    ->orWhereRaw("lower(name) like '%headquarters%'")
                    ->orWhereRaw("lower(name) like '%service headquarters%'")
                    ->orWhereRaw("lower(name) like '%shq%'");
            })
            ->orderByRaw("case when upper(coalesce(code,'')) = 'SHQ' then 0 else 1 end")
            ->orderBy('name')
            ->first();
    }

    private function accessibleFormationsForUser(User $user)
    {
        if ($user->isMainAdmin() || $user->isSuperAdmin()) {
            return Formation::orderBy('name')->get(['id', 'name', 'code']);
        }

        if ($user->isDCG() || $user->isPSO()) {
            $shq = $this->shqFormation();
            return $shq ? collect([$shq]) : collect();
        }

        return Formation::query()
            ->whereKey($user->formation_id)
            ->orderBy('name')
            ->get(['id', 'name', 'code']);
    }

    private function accessibleDirectoratesForUser(User $user)
    {
        if ($user->isDCG() && $user->directorate_id) {
            return Directorate::query()
                ->whereKey($user->directorate_id)
                ->orderBy('name')
                ->get(['id', 'name', 'code', 'type']);
        }

        if ($user->isPSO() || $user->isMainAdmin() || $user->isSuperAdmin()) {
            return Directorate::orderBy('name')->get(['id', 'name', 'code', 'type']);
        }

        return collect();
    }

    public function index()
    {
        $user = auth()->user();

        $formations = $this->accessibleFormationsForUser($user);

        $offices = Office::query()
            ->with(['formation:id,name,code', 'directorate:id,name,code,type'])
            ->whereIn('formation_id', $formations->pluck('id'))
            ->get(['id', 'formation_id', 'directorate_id', 'name', 'type', 'parent_id']);

        return view('offices.index', [
            'organogram' => $this->buildOrganogram($formations, $offices),
        ]);
    }

    public function create()
    {
        $user = auth()->user();
        $formations = $this->accessibleFormationsForUser($user);

        $formationIds = $formations->pluck('id')->all();
        $existingOffices = Office::query()
            ->whereIn('formation_id', $formationIds)
            ->orderBy('name')
            ->get(['id', 'formation_id', 'directorate_id', 'name', 'type', 'parent_id']);

        $directorates = $this->accessibleDirectoratesForUser($user);

        return view('offices.create', compact('formations', 'existingOffices', 'directorates'));
    }

    public function store(Request $request)
    {
        $user = auth()->user();

        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'directorate_id' => ['nullable', 'exists:directorates,id'],
        ];
        if ($user->isMainAdmin() || $user->isSuperAdmin()) {
            $rules['formation_id'] = ['required', 'exists:formations,id'];
        }

        $formationId = ($user->isMainAdmin() || $user->isSuperAdmin())
            ? $request->formation_id
            : ($this->accessibleFormationsForUser($user)->first()?->id ?? $user->formation_id);
        if (! $formationId) {
            return back()->withErrors(['formation_id' => 'No formation available for this account.'])->withInput();
        }

        $formation = Formation::query()->find($formationId);
        $formationCode = strtoupper((string) ($formation?->code ?? ''));
        $formationName = strtolower((string) ($formation?->name ?? ''));
        $isServiceHq = $formationCode === 'SHQ' || str_contains($formationName, 'service headquarters') || str_contains($formationName, 'headquarters') || str_contains($formationName, 'shq');

        $allowedTypes = $isServiceHq ? ['Division', 'Section', 'Unit'] : ['Section', 'Unit'];

        $rules['type'] = ['required', 'string', Rule::in($allowedTypes)];
        $rules['parent_ref'] = ['nullable', 'string'];

        $request->validate($rules);

        $type = (string) $request->type;
        $parentOfficeId = null;
        $parentDirectorateId = null;

        if ($isServiceHq) {
            if ($user->isDCG() && $user->directorate_id) {
                $parentDirectorateId = (int) $user->directorate_id;
            } elseif ($request->filled('directorate_id')) {
                $parentDirectorateId = (int) $request->directorate_id;
            }
        }

        // Then, process parent_ref, which might override or set parentOfficeId/parentDirectorateId
        if ($request->filled('parent_ref')) {
            $ref = (string) $request->parent_ref;
            if (str_starts_with($ref, 'directorate:')) {
                $parentDirectorateId = (int) str_replace('directorate:', '', $ref);
                $parentOfficeId = null; // Ensure parent_id is null if parent is a directorate
            } elseif (str_starts_with($ref, 'office:')) {
                $parentOfficeId = (int) str_replace('office:', '', $ref);
                // If parent is an office, its directorate_id should be inherited
                $parentOffice = Office::whereKey($parentOfficeId)->first();
                if ($parentOffice) {
                    $parentDirectorateId = $parentOffice->directorate_id;
                }
            }
        }

        if (! $isServiceHq) {
            if ($type === 'Section') {
                $parentOfficeId = null;
                $parentDirectorateId = null;
            } elseif ($type === 'Unit') {
                if ($parentOfficeId === null) {
                    return back()->withErrors(['parent_ref' => 'Please select a parent section.'])->withInput();
                }

                $parentOk = Office::query()
                    ->whereKey($parentOfficeId)
                    ->where('formation_id', $formationId)
                    ->where('type', 'Section')
                    ->exists();

                if (! $parentOk) {
                    return back()->withErrors(['parent_ref' => 'Invalid parent selection.'])->withInput();
                }
            }
        } else {
            if ($type === 'Division') {
                $parentOfficeId = null;
                if ($parentDirectorateId === null) {
                    return back()->withErrors(['parent_ref' => 'Please select a parent directorate.'])->withInput();
                }

                $dirOk = Directorate::query()->whereKey($parentDirectorateId)->exists();
                if (! $dirOk) {
                    return back()->withErrors(['parent_ref' => 'Invalid parent directorate.'])->withInput();
                }
            } elseif ($type === 'Section') {
                if ($parentOfficeId === null && $parentDirectorateId === null) {
                    return back()->withErrors(['parent_ref' => 'Please select a parent.'])->withInput();
                }
                if ($parentOfficeId !== null && $parentDirectorateId !== null) {
                    return back()->withErrors(['parent_ref' => 'Please select only one parent.'])->withInput();
                }

                if ($parentOfficeId !== null) {
                    $parentOk = Office::query()
                        ->whereKey($parentOfficeId)
                        ->where('formation_id', $formationId)
                        ->where('type', 'Division')
                        ->exists();
                    if (! $parentOk) {
                        return back()->withErrors(['parent_ref' => 'Invalid parent division.'])->withInput();
                    }
                    $parentDirectorateId = Office::whereKey($parentOfficeId)->value('directorate_id');
                } else {
                    $dirOk = Directorate::query()->whereKey($parentDirectorateId)->exists();
                    if (! $dirOk) {
                        return back()->withErrors(['parent_ref' => 'Invalid parent directorate.'])->withInput();
                    }
                }
            } elseif ($type === 'Unit') {
                if ($parentOfficeId === null && $parentDirectorateId === null) {
                    return back()->withErrors(['parent_ref' => 'Please select a parent.'])->withInput();
                }
                if ($parentOfficeId !== null && $parentDirectorateId !== null) {
                    return back()->withErrors(['parent_ref' => 'Please select only one parent.'])->withInput();
                }

                if ($parentOfficeId !== null) {
                    $parentOk = Office::query()
                        ->whereKey($parentOfficeId)
                        ->where('formation_id', $formationId)
                        ->whereIn('type', ['Section', 'Division'])
                        ->exists();
                    if (! $parentOk) {
                        return back()->withErrors(['parent_ref' => 'Invalid parent office.'])->withInput();
                    }
                    $parentDirectorateId = Office::whereKey($parentOfficeId)->value('directorate_id');
                    if (! $parentDirectorateId) {
                        return back()->withErrors(['parent_ref' => 'Parent office is missing a directorate.'])->withInput();
                    }
                } else {
                    $dirOk = Directorate::query()->whereKey($parentDirectorateId)->exists();
                    if (! $dirOk) {
                        return back()->withErrors(['parent_ref' => 'Invalid parent directorate.'])->withInput();
                    }
                }
            }
        }

        if ($isServiceHq) {
            if ($user->isDCG() && $user->directorate_id) {
                $parentDirectorateId = (int) $user->directorate_id;
            } elseif ($request->filled('directorate_id')) {
                $parentDirectorateId = (int) $request->directorate_id;
            }
        }

        Office::create([
            'name' => (string) $request->name,
            'formation_id' => $formationId,
            'type' => $type,
            'parent_id' => $parentOfficeId,
            'directorate_id' => $parentDirectorateId,
        ]);

        return redirect()->route('offices.index')->with('success', 'Office created successfully.');
    }

    public function destroy(Office $office)
    {
        $user = auth()->user();

        if (! ($user->isMainAdmin() || $user->isSuperAdmin())) {
            $allowedFormationIds = $this->accessibleFormationsForUser($user)->pluck('id')->map(fn ($v) => (int) $v)->all();
            if (! in_array((int) $office->formation_id, $allowedFormationIds, true)) {
                abort(403);
            }
        }

        if ($office->children()->exists()) {
            return redirect()->route('offices.index')->with('error', 'Cannot delete an office that has child offices.');
        }

        $office->delete();

        return redirect()->route('offices.index')->with('success', 'Office deleted successfully.');
    }

    private const TYPE_ORDER = [
        'Division' => 1,
        'Section' => 2,
        'Unit' => 3,
    ];

    private function buildOrganogram($formations, $offices): array
    {
        $result = [];

        foreach ($formations as $formation) {
            $formationOffices = $offices->where('formation_id', $formation->id)->values();

            if (! $this->isServiceHq($formation)) {
                $result[] = [
                    'formation' => $formation,
                    'is_service_hq' => false,
                    'roots' => $this->buildOfficeTree($formationOffices->whereNull('directorate_id')->values()),
                    'directorates' => [],
                ];
                continue;
            }

            $directorates = [];

            $directorateIds = $formationOffices
                ->pluck('directorate_id')
                ->filter()
                ->unique()
                ->values();

            foreach ($directorateIds as $directorateId) {
                $dirOffice = $formationOffices->firstWhere('directorate_id', $directorateId);

                $directorates[] = [
                    'directorate' => $dirOffice?->directorate,
                    'directorate_id' => (int) $directorateId,
                    'roots' => $this->buildOfficeTree(
                        $formationOffices->where('directorate_id', $directorateId)->values()
                    ),
                ];
            }

            $noDirectorate = $formationOffices->whereNull('directorate_id')->values();
            if ($noDirectorate->isNotEmpty()) {
                $directorates[] = [
                    'directorate' => null,
                    'directorate_id' => null,
                    'roots' => $this->buildOfficeTree($noDirectorate),
                ];
            }

            $result[] = [
                'formation' => $formation,
                'is_service_hq' => true,
                'roots' => [],
                'directorates' => $directorates,
            ];
        }

        return $result;
    }

    private function buildOfficeTree($offices): array
    {
        $childrenByParent = $offices->groupBy('parent_id');

        return $childrenByParent
            ->get(null, collect())
            ->sortBy(fn (Office $o) => $this->officeSortKey($o))
            ->values()
            ->map(fn (Office $o) => $this->buildOfficeNode($o, $childrenByParent))
            ->all();
    }

    private function buildOfficeNode(Office $office, $childrenByParent): array
    {
        $children = $childrenByParent
            ->get($office->id, collect())
            ->sortBy(fn (Office $o) => $this->officeSortKey($o))
            ->values()
            ->map(fn (Office $o) => $this->buildOfficeNode($o, $childrenByParent))
            ->all();

        return [
            'office' => $office,
            'children' => $children,
        ];
    }

    private function officeSortKey(Office $office): string
    {
        $order = self::TYPE_ORDER[$office->type] ?? 99;
        return str_pad((string) $order, 2, '0', STR_PAD_LEFT) . '|' . mb_strtolower((string) $office->name);
    }

    private function isServiceHq(Formation $formation): bool
    {
        $code = strtoupper((string) ($formation->code ?? ''));
        $name = strtolower((string) ($formation->name ?? ''));

        return $code === 'SHQ'
            || str_contains($name, 'service headquarters')
            || str_contains($name, 'headquarters')
            || str_contains($name, 'shq');
    }
}
