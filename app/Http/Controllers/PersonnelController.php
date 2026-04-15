<?php

namespace App\Http\Controllers;

use App\Models\CustomField;
use App\Models\CustomFieldValue;
use App\Models\Directorate;
use App\Models\Formation;
use App\Models\FormationPosting;
use App\Models\Office;
use App\Models\OfficePosting;
use App\Models\PromotionHistory;
use App\Models\State;
use App\Models\User;
use App\Services\NotificationService;
use App\Support\PersonnelExport;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class PersonnelController extends Controller
{
    public function index()
    {
        $currentUser = auth()->user();
        $status = (string) request('status', '');
        if (! in_array($status, ['completed', 'incomplete'], true)) {
            $legacyTab = (string) request('tab', '');
            $status = in_array($legacyTab, ['completed', 'incomplete'], true) ? $legacyTab : 'completed';
        }

        $query = User::with(['formation', 'office', 'state', 'lga'])
            ->where('employment_status', 'Active');
        $query->whereNotIn('role', ['Super Admin', 'Main Admin', 'Formation Admin', 'DCG', 'Principal Staff Officer (PSO)', 'Viewer']);

        if ($currentUser->isOfficeAdmin()) {
            $query->where('office_id', $currentUser->office_id);
        } elseif ($currentUser->isDCG()) {
            $query->where('directorate_id', $currentUser->directorate_id);
        } elseif (! ($currentUser->isMainAdmin() || $currentUser->isSuperAdmin())) {
            $query->where('formation_id', $currentUser->formation_id);
        }

        if (request('q')) {
            $q = request('q');
            $query->where(function ($sub) use ($q) {
                $sub->where('surname', 'like', "%{$q}%")
                    ->orWhere('first_name', 'like', "%{$q}%")
                    ->orWhere('other_names', 'like', "%{$q}%")
                    ->orWhere('nis_no', 'like', "%{$q}%")
                    ->orWhere('email', 'like', "%{$q}%");
            });
        }

        if (request()->filled('gender')) {
            $query->where('gender', request('gender'));
        }

        if (request()->filled('rank_code')) {
            $query->where('rank_code', request('rank_code'));
        }

        if (($currentUser->isMainAdmin() || $currentUser->isSuperAdmin()) && request()->filled('formation_id')) {
            $query->where('formation_id', request('formation_id'));
        }

        if (request()->filled('directorate_id')) {
            $query->where('directorate_id', request('directorate_id'));
        }

        if (request()->filled('office_id')) {
            $query->where('office_id', request('office_id'));
        }

        if (request()->filled('dopa')) {
            $query->whereDate('date_of_present_appointment', request('dopa'));
        }

        if (request()->filled('dopp')) {
            $query->whereDate('date_of_present_posting_to_formation', request('dopp'));
        }

        $shqFormationIds = Formation::query()
            ->where('code', 'SHQ')
            ->orWhere('name', 'like', '%headquarters%')
            ->pluck('id')
            ->all();

        $requiredStrings = ['nis_no', 'surname', 'first_name', 'email', 'gender', 'rank_code'];
        $requiredDates = ['date_of_birth', 'date_of_first_appointment', 'date_of_present_appointment'];
        $requiredIds = ['state_id', 'lga_id', 'formation_id'];

        if ($status === 'incomplete') {
            $query->where(function ($sub) use ($requiredStrings, $requiredDates, $requiredIds, $shqFormationIds) {
                foreach ($requiredStrings as $col) {
                    $sub->orWhereNull($col)->orWhere($col, '');
                }
                foreach ($requiredDates as $col) {
                    $sub->orWhereNull($col);
                }
                foreach ($requiredIds as $col) {
                    $sub->orWhereNull($col);
                }
                if ($shqFormationIds !== []) {
                    $sub->orWhere(function ($q) use ($shqFormationIds) {
                        $q->whereIn('formation_id', $shqFormationIds)->whereNull('directorate_id');
                    });
                }
            });
        } else {
            foreach ($requiredStrings as $col) {
                $query->whereNotNull($col)->where($col, '<>', '');
            }
            foreach ($requiredDates as $col) {
                $query->whereNotNull($col);
            }
            foreach ($requiredIds as $col) {
                $query->whereNotNull($col);
            }
            if ($shqFormationIds !== []) {
                $query->where(function ($q) use ($shqFormationIds) {
                    $q->whereNotIn('formation_id', $shqFormationIds)->orWhereNotNull('directorate_id');
                });
            }
        }

        $personnel = $query->orderBy('surname')->paginate(15)->withQueryString();

        $exportFieldLabels = PersonnelExport::labelsWithCustomFields();
        $defaultExportFields = PersonnelExport::defaultFields();
        $customFields = CustomField::orderBy('label')->get();
        $formations = ($currentUser->isMainAdmin() || $currentUser->isSuperAdmin())
            ? Formation::orderBy('name')->get(['id', 'name', 'code'])
            : Formation::where('id', $currentUser->formation_id)->get(['id', 'name', 'code']);
        $directorates = Directorate::orderBy('name')->get(['id', 'name', 'code']);
        $ranks = json_decode(File::get(database_path('data/ranks.json')), true);

        if (request()->boolean('ajax')) {
            return response()->json([
                'html' => view('personnel.partials.table', compact('personnel'))->render(),
            ]);
        }

        return view('personnel.index', compact('personnel', 'exportFieldLabels', 'defaultExportFields', 'customFields', 'formations', 'directorates', 'ranks'));
    }

    public function inactive()
    {
        $currentUser = auth()->user();
        if (! ($currentUser->isMainAdmin() || $currentUser->isSuperAdmin())) {
            abort(403);
        }

        $query = User::with(['formation', 'office'])->where('employment_status', 'Inactive');
        $query->whereNotIn('role', ['Super Admin', 'Main Admin', 'Formation Admin', 'DCG', 'Principal Staff Officer (PSO)', 'Viewer']);

        if (request('q')) {
            $q = request('q');
            $query->where(function ($sub) use ($q) {
                $sub->where('surname', 'like', "%{$q}%")
                    ->orWhere('first_name', 'like', "%{$q}%")
                    ->orWhere('other_names', 'like', "%{$q}%")
                    ->orWhere('nis_no', 'like', "%{$q}%")
                    ->orWhere('email', 'like', "%{$q}%");
            });
        }

        $personnel = $query->orderBy('surname')->paginate(15)->withQueryString();

        return view('personnel.inactive', compact('personnel'));
    }

    public function promotions()
    {
        $currentUser = auth()->user();
        if (! $currentUser->isMainAdmin()) {
            abort(403);
        }

        $q = trim((string) request('q', ''));

        $query = User::with(['formation', 'office'])->withCount('promotions')
            ->where('employment_status', 'Active')
            ->whereNotIn('role', ['Super Admin', 'Main Admin', 'Formation Admin', 'DCG', 'Principal Staff Officer (PSO)', 'Viewer'])
            ->whereNotNull('rank_code')
            ->where('rank_code', '<>', '');

        if ($q !== '') {
            $query->where(function ($sub) use ($q) {
                $sub->where('surname', 'like', "%{$q}%")
                    ->orWhere('first_name', 'like', "%{$q}%")
                    ->orWhere('other_names', 'like', "%{$q}%")
                    ->orWhere('nis_no', 'like', "%{$q}%");
            });
        }

        $personnel = $query->orderBy('surname')->paginate(20)->withQueryString();
        $rankOrder = collect(json_decode(File::get(database_path('data/ranks.json')), true))
            ->pluck('code')
            ->values()
            ->all();

        return view('personnel.promotions', compact('personnel', 'rankOrder'));
    }

    public function promote(Request $request, User $user)
    {
        $currentUser = auth()->user();
        if (! $currentUser->isMainAdmin()) {
            abort(403);
        }

        if ($user->employment_status !== 'Active') {
            return redirect()->route('personnel.promotions')->with('error', 'Only active personnel can be promoted.');
        }

        $request->attributes->set('audit_before', $this->auditSnapshot($user));

        $request->validate([
            'effective_date' => ['required', 'date'],
        ]);

        $ranks = collect(json_decode(File::get(database_path('data/ranks.json')), true));
        $rankOrder = $ranks->pluck('code')->values();
        $currentCode = (string) $user->rank_code;
        $currentIndex = $rankOrder->search($currentCode, true);

        if ($currentIndex === false || $currentIndex === 0) {
            return redirect()->route('personnel.promotions')->with('error', 'Personnel is already at the highest rank or has an invalid rank.');
        }

        $nextCode = (string) $rankOrder[$currentIndex - 1];
        $nextRank = $ranks->firstWhere('code', $nextCode);
        $effectiveDate = Carbon::parse($request->input('effective_date'))->toDateString();

        $user->update([
            'rank_code' => $nextCode,
            'rank' => (string) ($nextRank['name'] ?? $nextCode),
            'date_of_present_appointment' => $effectiveDate,
            'date_of_present_promotion' => $effectiveDate,
        ]);

        PromotionHistory::create([
            'user_id' => $user->id,
            'rank_code' => $nextCode,
            'rank_name' => (string) ($nextRank['name'] ?? $nextCode),
            'effective_date' => $effectiveDate,
            'remark' => 'Promoted',
        ]);

        $computedRetirementDate = $user->retirementDate();
        if ($computedRetirementDate) {
            $user->update(['date_of_retirement' => $computedRetirementDate->toDateString()]);
        }

        $request->attributes->set('audit_after', $this->auditSnapshot($user->fresh()));

        $this->notifyPersonnelAndAdmins(
            $user,
            'promotion',
            'Personnel promoted',
            trim("{$user->surname} {$user->first_name} promoted to {$user->rank_code}."),
            ['effective_date' => $effectiveDate]
        );

        return redirect()->route('personnel.promotions')->with('success', "Promotion successful for {$user->surname}, {$user->first_name}.");
    }

    public function undoPromotion(User $user)
    {
        $currentUser = auth()->user();
        if (! $currentUser->isMainAdmin()) {
            abort(403);
        }

        request()->attributes->set('audit_before', $this->auditSnapshot($user));

        $history = $user->promotions()->orderByDesc('effective_date')->orderByDesc('id')->get();
        if ($history->count() < 2) {
            return redirect()->route('personnel.promotions')->with('error', 'No promotion to undo for this personnel.');
        }

        $latest = $history->first();
        $previous = $history->get(1);
        if (! $previous) {
            return redirect()->route('personnel.promotions')->with('error', 'No previous rank record found.');
        }

        $user->update([
            'rank_code' => (string) $previous->rank_code,
            'rank' => (string) $previous->rank_name,
            'date_of_present_appointment' => $previous->effective_date?->toDateString(),
            'date_of_present_promotion' => $previous->effective_date?->toDateString(),
        ]);

        $latest->delete();

        $computedRetirementDate = $user->retirementDate();
        if ($computedRetirementDate) {
            $user->update(['date_of_retirement' => $computedRetirementDate->toDateString()]);
        }

        request()->attributes->set('audit_after', $this->auditSnapshot($user->fresh()));

        $this->notifyPersonnelAndAdmins(
            $user,
            'promotion_undo',
            'Promotion reversed',
            trim("Last promotion was undone for {$user->surname} {$user->first_name}.")
        );

        return redirect()->route('personnel.promotions')->with('success', "Last promotion was undone for {$user->surname}, {$user->first_name}.");
    }

    public function retireForm(User $user)
    {
        $currentUser = auth()->user();
        if (! ($currentUser->isMainAdmin() || $currentUser->isSuperAdmin())) {
            abort(403);
        }

        return view('personnel.retire', compact('user'));
    }

    public function retireStore(Request $request, User $user)
    {
        $currentUser = auth()->user();
        if (! ($currentUser->isMainAdmin() || $currentUser->isSuperAdmin())) {
            abort(403);
        }

        $validated = $request->validate([
            'retirement_reason' => ['required', 'string', 'in:Retire,Resign,Dismiss,Other'],
            'other_reason' => ['nullable', 'string', 'max:255'],
            'retired_at' => ['nullable', 'date'],
            'remark' => ['nullable', 'string', 'max:255'],
        ]);

        $reason = $validated['retirement_reason'] === 'Other'
            ? ($validated['other_reason'] ?: 'Other')
            : $validated['retirement_reason'];

        $retiredAt = isset($validated['retired_at']) ? Carbon::parse($validated['retired_at'])->toDateString() : Carbon::today()->toDateString();
        $computedRetirementDate = $user->retirementDate();

        $user->update([
            'employment_status' => 'Inactive',
            'retired_at' => $retiredAt,
            'retirement_reason' => $reason,
            'date_of_retirement' => $computedRetirementDate?->toDateString(),
        ]);

        return redirect()->route('personnel.inactive')->with('success', 'Personnel moved to inactive list.');
    }

    public function create()
    {
        $currentUser = auth()->user();
        $states = State::orderBy('name')->get();
        $ranks = json_decode(File::get(database_path('data/ranks.json')), true);
        $banks = json_decode(File::get(database_path('data/banks.json')), true);
        $qualifications = json_decode(File::get(database_path('data/qualifications.json')), true);
        $directorates = Directorate::orderBy('name')->get();

        if ($currentUser->isMainAdmin() || $currentUser->isSuperAdmin()) {
            $formations = Formation::orderBy('name')->get();
        } else {
            $formations = Formation::where('id', $currentUser->formation_id)->get();
        }

        $offices = collect();
        if (! ($currentUser->isMainAdmin() || $currentUser->isSuperAdmin())) {
            if ($currentUser->isOfficeAdmin() && $currentUser->office_id) {
                $offices = Office::whereKey($currentUser->office_id)->orderBy('name')->get();
            } elseif (($currentUser->isDCG() || $currentUser->isPSO()) && $currentUser->directorate_id) {
                $offices = Office::where('directorate_id', $currentUser->directorate_id)->orderBy('name')->get();
            } else {
                $offices = Office::where('formation_id', $currentUser->formation_id)->orderBy('name')->get();
            }
        }

        $customFields = CustomField::orderBy('label')->get();
        $presetRole = $currentUser->isMainAdmin() ? request('role') : null;
        $presetFormationId = $currentUser->isMainAdmin() ? request('formation_id') : null;
        $presetDirectorateId = $currentUser->isMainAdmin() ? request('directorate_id') : null;
        $showOfficeField = ! ($currentUser->isMainAdmin() || $currentUser->isSuperAdmin());

        return view('personnel.create', compact('states', 'formations', 'ranks', 'banks', 'qualifications', 'offices', 'directorates', 'customFields', 'presetRole', 'presetFormationId', 'presetDirectorateId', 'showOfficeField'));
    }

    public function store(Request $request)
    {
        $currentUser = auth()->user();

        $allowedRoles = ['User'];
        if ($currentUser->isMainAdmin()) {
            $allowedRoles = [
                'Office Admin',
                'User',
            ];
        }

        $customFields = CustomField::orderBy('id')->get();

        $rules = [
            'nis_no' => ['required', 'string', 'max:255', 'unique:users'],
            'surname' => ['required', 'string', 'max:255'],
            'first_name' => ['required', 'string', 'max:255'],
            'other_names' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:30'],
            'nok_name' => ['nullable', 'string', 'max:255'],
            'nok_phone' => ['nullable', 'string', 'max:30'],
            'qualification' => ['nullable', 'string', 'max:255'],
            'field_of_study' => ['nullable', 'string', 'max:255'],
            'salary_account_number' => ['nullable', 'string', 'max:50'],
            'bank' => ['nullable', 'string', 'max:255'],
            'pfa_number' => ['nullable', 'string', 'max:100'],
            'pfa_name' => ['nullable', 'string', 'max:255'],
            'nhf_no' => ['nullable', 'string', 'max:100'],
            'ippis_no' => ['nullable', 'string', 'max:100'],
            'remark' => ['nullable', 'string'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:users'],
            'gender' => ['required', 'string', 'in:Male,Female'],
            'rank_code' => ['required', 'string', 'max:20'],
            'date_of_birth' => ['required', 'date'],
            'state_id' => ['required', 'exists:states,id'],
            'lga_id' => ['required', 'exists:lgas,id'],
            'date_of_first_appointment' => ['required', 'date'],
            'date_of_present_appointment' => ['required', 'date'],
            'date_of_present_posting_to_formation' => ['nullable', 'date'],
            'formation_id' => ($currentUser->isMainAdmin() || $currentUser->isSuperAdmin()) ? ['required', 'exists:formations,id'] : ['nullable'],
            'office_id' => ($currentUser->isMainAdmin() || $currentUser->isSuperAdmin())
                ? ['prohibited']
                : [
                    'nullable',
                    Rule::exists('offices', 'id')
                        ->when(
                            $currentUser->isOfficeAdmin(),
                            fn ($rule) => $rule->where('id', $currentUser->office_id)
                        )
                        ->when(
                            $currentUser->isDCG() || ($currentUser->isPSO() && $currentUser->directorate_id),
                            fn ($rule) => $rule->where('directorate_id', $currentUser->directorate_id)
                        )
                        ->when(
                            ! ($currentUser->isOfficeAdmin() || $currentUser->isDCG() || ($currentUser->isPSO() && $currentUser->directorate_id)),
                            fn ($rule) => $rule->where('formation_id', $currentUser->formation_id)
                        ),
                ],
            'directorate_id' => ['nullable', 'exists:directorates,id'],
            'role' => ['required', 'string', Rule::in($allowedRoles)],
        ];

        foreach ($customFields as $field) {
            $key = "custom.{$field->key}";
            $base = $field->required ? ['required'] : ['nullable'];

            $rules[$key] = match ($field->type) {
                'number' => [...$base, 'numeric'],
                'date' => [...$base, 'date'],
                'select' => [...$base, 'string', Rule::in($field->options ?? [])],
                default => [...$base, 'string', 'max:1000'],
            };
        }

        $request->validate($rules);

        $targetRole = $request->role;
        if ($targetRole !== 'User' && ! $currentUser->isMainAdmin()) {
            abort(403);
        }

        $formationId = ($currentUser->isMainAdmin() || $currentUser->isSuperAdmin()) ? $request->formation_id : $currentUser->formation_id;
        $officeId = null;
        if (! ($currentUser->isMainAdmin() || $currentUser->isSuperAdmin()) && $request->filled('office_id')) {
            if ($currentUser->isOfficeAdmin()) {
                $officeId = (int) $currentUser->office_id;
            } elseif ($currentUser->isDCG() || ($currentUser->isPSO() && $currentUser->directorate_id)) {
                $officeId = Office::where('directorate_id', $currentUser->directorate_id)->where('id', $request->office_id)->value('id');
            } else {
                $officeId = Office::where('formation_id', $formationId)->where('id', $request->office_id)->value('id');
            }
        }

        $formation = Formation::find($formationId);
        $directorateId = null;
        if ($this->isServiceHqFormation($formation) && $request->filled('directorate_id')) {
            $directorateId = $request->directorate_id;
        }

        $ranks = json_decode(File::get(database_path('data/ranks.json')), true);
        $rankName = collect($ranks)->firstWhere('code', $request->rank_code)['name'] ?? null;

        $user = User::create([
            'name' => $request->surname.' '.$request->first_name,
            'email' => $request->email,
            'password' => Hash::make($request->nis_no),
            'must_change_password' => true,
            'nis_no' => $request->nis_no,
            'surname' => $request->surname,
            'first_name' => $request->first_name,
            'other_names' => $request->other_names,
            'phone' => $request->phone,
            'nok_name' => $request->nok_name,
            'nok_phone' => $request->nok_phone,
            'qualification' => $request->qualification,
            'field_of_study' => $request->field_of_study,
            'salary_account_number' => $request->salary_account_number,
            'bank' => $request->bank,
            'pfa_number' => $request->pfa_number,
            'pfa_name' => $request->pfa_name,
            'nhf_no' => $request->nhf_no,
            'ippis_no' => $request->ippis_no,
            'remark' => $request->remark,
            'gender' => $request->gender,
            'rank' => $rankName,
            'rank_code' => $request->rank_code,
            'date_of_birth' => $request->date_of_birth,
            'state_id' => $request->state_id,
            'lga_id' => $request->lga_id,
            'date_of_first_appointment' => $request->date_of_first_appointment,
            'date_of_present_appointment' => $request->date_of_present_appointment,
            'date_of_present_posting_to_formation' => $request->date_of_present_posting_to_formation,
            'formation_id' => $formationId,
            'office_id' => $officeId,
            'directorate_id' => $directorateId,
            'role' => $request->role,
            'employment_status' => 'Active',
        ]);

        foreach ($customFields as $field) {
            $value = $request->input("custom.{$field->key}");
            if ($value === null || $value === '') {
                continue;
            }

            CustomFieldValue::create([
                'custom_field_id' => $field->id,
                'user_id' => $user->id,
                'value' => is_array($value) ? json_encode($value) : (string) $value,
            ]);
        }

        $effectiveFormationDate = $request->date_of_present_posting_to_formation
            ? Carbon::parse($request->date_of_present_posting_to_formation)->toDateString()
            : Carbon::parse($request->date_of_present_appointment)->toDateString();

        FormationPosting::create([
            'user_id' => $user->id,
            'formation_id' => $formationId,
            'effective_date' => $effectiveFormationDate,
            'remark' => 'Current Posting',
        ]);

        OfficePosting::create([
            'user_id' => $user->id,
            'office_id' => $officeId,
            'effective_date' => $effectiveFormationDate,
            'remark' => 'Current Office',
        ]);

        PromotionHistory::create([
            'user_id' => $user->id,
            'rank_code' => $user->rank_code,
            'rank_name' => $user->rank,
            'effective_date' => Carbon::parse($request->date_of_present_appointment)->toDateString(),
            'remark' => 'Current Rank',
        ]);

        $computedRetirementDate = $user->retirementDate();
        if ($computedRetirementDate) {
            $user->update(['date_of_retirement' => $computedRetirementDate->toDateString()]);
        }

        return redirect()->route('personnel.index')->with('success', 'Personnel registered successfully.');
    }

    public function edit(User $user)
    {
        $currentUser = auth()->user();
        if (! ($currentUser->isMainAdmin() || $currentUser->isSuperAdmin())) {
            if ($currentUser->isOfficeAdmin()) {
                if ((int) $user->office_id !== (int) $currentUser->office_id) {
                    abort(403);
                }
            } elseif ($currentUser->isDCG()) {
                if ((int) $user->directorate_id !== (int) $currentUser->directorate_id) {
                    abort(403);
                }
            } elseif ((int) $user->formation_id !== (int) $currentUser->formation_id) {
                abort(403);
            }
        }

        $states = State::orderBy('name')->get();
        $ranks = json_decode(File::get(database_path('data/ranks.json')), true);
        $banks = json_decode(File::get(database_path('data/banks.json')), true);
        $qualifications = json_decode(File::get(database_path('data/qualifications.json')), true);
        $directorates = Directorate::orderBy('name')->get();

        if ($currentUser->isMainAdmin() || $currentUser->isSuperAdmin()) {
            $formations = Formation::orderBy('name')->get();
        } else {
            $formations = Formation::where('id', $currentUser->formation_id)->get();
        }

        $offices = collect();
        if (! ($currentUser->isMainAdmin() || $currentUser->isSuperAdmin())) {
            if ($currentUser->isOfficeAdmin() && $currentUser->office_id) {
                $offices = Office::whereKey($currentUser->office_id)->orderBy('name')->get();
            } elseif (($currentUser->isDCG() || $currentUser->isPSO()) && $currentUser->directorate_id) {
                $offices = Office::where('directorate_id', $currentUser->directorate_id)->orderBy('name')->get();
            } elseif ($currentUser->formation_id) {
                $offices = Office::where('formation_id', $currentUser->formation_id)->orderBy('name')->get();
            }
        }
        if ($offices->isEmpty() && $user->formation_id) {
            $offices = Office::where('formation_id', $user->formation_id)->orderBy('name')->get();
        }

        $customFields = CustomField::orderBy('label')->get();
        $customValues = $user->customFieldValues()->pluck('value', 'custom_field_id')->all();

        $canEditRemark = $currentUser->isMainAdmin()
            || $currentUser->isSuperAdmin()
            || $currentUser->isFormationAdmin()
            || $currentUser->isOfficeAdmin()
            || $currentUser->isDCG();

        $canMoveOffice = $currentUser->isDCG()
            || $currentUser->isFormationAdmin()
            || $currentUser->isPSO();

        $lockFilledFields = ! ($currentUser->isMainAdmin() || $currentUser->isSuperAdmin())
            && $currentUser->hasAbility('personnel.edit');

        $showOfficeField = ! ($currentUser->isMainAdmin() || $currentUser->isSuperAdmin());

        return view('personnel.edit', compact('user', 'states', 'formations', 'ranks', 'banks', 'qualifications', 'offices', 'directorates', 'customFields', 'customValues', 'lockFilledFields', 'canEditRemark', 'canMoveOffice', 'showOfficeField'));
    }

    public function show(User $user)
    {
        $currentUser = auth()->user();
        if (! ($currentUser->isMainAdmin() || $currentUser->isSuperAdmin())) {
            if ($currentUser->isOfficeAdmin()) {
                if ((int) $user->office_id !== (int) $currentUser->office_id) {
                    abort(403);
                }
            } elseif ($currentUser->isDCG()) {
                if ((int) $user->directorate_id !== (int) $currentUser->directorate_id) {
                    abort(403);
                }
            } elseif ((int) $user->formation_id !== (int) $currentUser->formation_id) {
                abort(403);
            }
        }

        $user->load(['formation', 'office', 'directorate', 'state', 'lga', 'customFieldValues.field']);
        $customFields = CustomField::orderBy('label')->get();
        $customValues = $user->customFieldValues->pluck('value', 'custom_field_id')->all();

        return view('personnel.show', compact('user', 'customFields', 'customValues'));
    }

    public function update(Request $request, User $user)
    {
        $currentUser = auth()->user();
        if (! ($currentUser->isMainAdmin() || $currentUser->isSuperAdmin())) {
            if ($currentUser->isOfficeAdmin()) {
                if ((int) $user->office_id !== (int) $currentUser->office_id) {
                    abort(403);
                }
            } elseif ($currentUser->isDCG()) {
                if ((int) $user->directorate_id !== (int) $currentUser->directorate_id) {
                    abort(403);
                }
            } elseif ((int) $user->formation_id !== (int) $currentUser->formation_id) {
                abort(403);
            }
        }

        $request->attributes->set('audit_before', $this->auditSnapshot($user));

        $allowedRoles = ['User'];
        if ($currentUser->isMainAdmin()) {
            $allowedRoles = [
                'Office Admin',
                'User',
            ];
        }

        $customFields = CustomField::orderBy('id')->get();
        $canEditRemark = $currentUser->isMainAdmin()
            || $currentUser->isSuperAdmin()
            || $currentUser->isFormationAdmin()
            || $currentUser->isOfficeAdmin()
            || $currentUser->isDCG();
        $canMoveOffice = $currentUser->isMainAdmin()
            || $currentUser->isSuperAdmin()
            || $currentUser->isDCG()
            || $currentUser->isFormationAdmin()
            || $currentUser->isPSO();
        $lockFilledFields = ! ($currentUser->isMainAdmin() || $currentUser->isSuperAdmin())
            && $currentUser->hasAbility('personnel.edit');

        $rules = [
            'surname' => ['required', 'string', 'max:255'],
            'first_name' => ['required', 'string', 'max:255'],
            'other_names' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:30'],
            'nok_name' => ['nullable', 'string', 'max:255'],
            'nok_phone' => ['nullable', 'string', 'max:30'],
            'qualification' => ['nullable', 'string', 'max:255'],
            'field_of_study' => ['nullable', 'string', 'max:255'],
            'salary_account_number' => ['nullable', 'string', 'max:50'],
            'bank' => ['nullable', 'string', 'max:255'],
            'pfa_number' => ['nullable', 'string', 'max:100'],
            'pfa_name' => ['nullable', 'string', 'max:255'],
            'nhf_no' => ['nullable', 'string', 'max:100'],
            'ippis_no' => ['nullable', 'string', 'max:100'],
            'remark' => ['nullable', 'string'],
            'gender' => ['required', 'string', 'in:Male,Female'],
            'rank_code' => ['required', 'string', 'max:20'],
            'date_of_birth' => ['required', 'date'],
            'state_id' => ['required', 'exists:states,id'],
            'lga_id' => ['required', 'exists:lgas,id'],
            'date_of_first_appointment' => ['required', 'date'],
            'date_of_present_appointment' => ['required', 'date'],
            'date_of_present_posting_to_formation' => ['nullable', 'date'],
            'formation_id' => ($currentUser->isMainAdmin() || $currentUser->isSuperAdmin()) ? ['required', 'exists:formations,id'] : ['nullable'],
            'office_id' => ($currentUser->isMainAdmin() || $currentUser->isSuperAdmin())
                ? ['prohibited']
                : [
                    'nullable',
                    Rule::exists('offices', 'id')
                        ->when(
                            $currentUser->isOfficeAdmin(),
                            fn ($rule) => $rule->where('id', $currentUser->office_id)
                        )
                        ->when(
                            $currentUser->isDCG() || ($currentUser->isPSO() && $currentUser->directorate_id),
                            fn ($rule) => $rule->where('directorate_id', $currentUser->directorate_id)
                        )
                        ->when(
                            ! ($currentUser->isOfficeAdmin() || $currentUser->isDCG() || ($currentUser->isPSO() && $currentUser->directorate_id)),
                            fn ($rule) => $rule->where('formation_id', $currentUser->formation_id)
                        ),
                ],
            'directorate_id' => ['nullable', 'exists:directorates,id'],
            'role' => ['required', 'string', Rule::in($allowedRoles)],
        ];

        foreach ($customFields as $field) {
            $key = "custom.{$field->key}";
            $base = $field->required ? ['required'] : ['nullable'];

            $rules[$key] = match ($field->type) {
                'number' => [...$base, 'numeric'],
                'date' => [...$base, 'date'],
                'select' => [...$base, 'string', Rule::in($field->options ?? [])],
                default => [...$base, 'string', 'max:1000'],
            };
        }

        if (! $canEditRemark) {
            $request->merge(['remark' => $user->remark]);
        }

        if ($lockFilledFields) {
            $locked = [];

            $lockedIfNotBlank = [
                'surname', 'first_name', 'other_names', 'phone',
                'nok_name', 'nok_phone',
                'qualification', 'field_of_study',
                'salary_account_number', 'bank',
                'pfa_number', 'pfa_name',
                'nhf_no', 'ippis_no',
                'remark',
                'gender', 'rank_code',
            ];

            foreach ($lockedIfNotBlank as $col) {
                $existing = trim((string) ($user->{$col} ?? ''));
                if ($existing !== '') {
                    $locked[$col] = $user->{$col};
                }
            }

            $lockedIfNotNull = ['state_id', 'lga_id', 'formation_id', 'directorate_id'];
            if (! $canMoveOffice) {
                $lockedIfNotNull[] = 'office_id';
            }
            foreach ($lockedIfNotNull as $col) {
                if ($user->{$col} !== null) {
                    $locked[$col] = $user->{$col};
                }
            }

            $lockedDateIfSet = [
                'date_of_birth',
                'date_of_first_appointment',
                'date_of_present_appointment',
                'date_of_present_posting_to_formation',
            ];
            foreach ($lockedDateIfSet as $col) {
                if ($user->{$col}) {
                    $locked[$col] = $user->{$col}?->format('Y-m-d');
                }
            }

            $locked['role'] = $user->role;

            $existingCustom = $user->customFieldValues()->pluck('value', 'custom_field_id')->all();
            $customInput = (array) $request->input('custom', []);
            foreach ($customFields as $field) {
                $existingValue = $existingCustom[$field->id] ?? null;
                if ($existingValue === null || $existingValue === '') {
                    continue;
                }
                $customInput[$field->key] = $existingValue;
            }

            $request->merge($locked);
            $request->merge(['custom' => $customInput]);
        }

        $request->validate($rules);

        $targetRole = $request->role;
        if ($targetRole !== 'User' && ! $currentUser->isMainAdmin()) {
            abort(403);
        }

        $formationId = ($currentUser->isMainAdmin() || $currentUser->isSuperAdmin()) ? $request->formation_id : $currentUser->formation_id;
        $oldOfficeId = $user->office_id;
        $officeId = $oldOfficeId;
        if ($request->filled('office_id')) {
            if ($canMoveOffice) {
                if ($currentUser->isDCG() || ($currentUser->isPSO() && $currentUser->directorate_id)) {
                    $officeId = Office::where('directorate_id', $currentUser->directorate_id)->where('id', $request->office_id)->value('id');
                } else {
                    $officeId = Office::where('formation_id', $formationId)->where('id', $request->office_id)->value('id');
                }
            }
        }

        $formation = Formation::find($formationId);
        $directorateId = null;
        if ($this->isServiceHqFormation($formation) && $request->filled('directorate_id')) {
            $directorateId = $request->directorate_id;
        }

        $ranks = json_decode(File::get(database_path('data/ranks.json')), true);
        $rankName = collect($ranks)->firstWhere('code', $request->rank_code)['name'] ?? null;

        $user->update([
            'name' => $request->surname.' '.$request->first_name,
            'surname' => $request->surname,
            'first_name' => $request->first_name,
            'other_names' => $request->other_names,
            'phone' => $request->phone,
            'nok_name' => $request->nok_name,
            'nok_phone' => $request->nok_phone,
            'qualification' => $request->qualification,
            'field_of_study' => $request->field_of_study,
            'salary_account_number' => $request->salary_account_number,
            'bank' => $request->bank,
            'pfa_number' => $request->pfa_number,
            'pfa_name' => $request->pfa_name,
            'nhf_no' => $request->nhf_no,
            'ippis_no' => $request->ippis_no,
            'remark' => $request->remark,
            'gender' => $request->gender,
            'rank' => $rankName,
            'rank_code' => $request->rank_code,
            'date_of_birth' => $request->date_of_birth,
            'state_id' => $request->state_id,
            'lga_id' => $request->lga_id,
            'date_of_first_appointment' => $request->date_of_first_appointment,
            'date_of_present_appointment' => $request->date_of_present_appointment,
            'date_of_present_posting_to_formation' => $request->date_of_present_posting_to_formation,
            'formation_id' => $formationId,
            'office_id' => $officeId,
            'directorate_id' => $directorateId,
            'role' => $request->role,
        ]);

        if ((int) $oldOfficeId !== (int) $officeId) {
            OfficePosting::where('user_id', $user->id)
                ->where('remark', 'Current Office')
                ->update(['remark' => 'Previous Office']);

            OfficePosting::create([
                'user_id' => $user->id,
                'office_id' => $officeId,
                'effective_date' => Carbon::today()->toDateString(),
                'remark' => 'Current Office',
            ]);
        }

        foreach ($customFields as $field) {
            $value = $request->input("custom.{$field->key}");
            if ($value === null || $value === '') {
                CustomFieldValue::where('user_id', $user->id)->where('custom_field_id', $field->id)->delete();

                continue;
            }

            CustomFieldValue::updateOrCreate(
                ['user_id' => $user->id, 'custom_field_id' => $field->id],
                ['value' => is_array($value) ? json_encode($value) : (string) $value]
            );
        }

        $computedRetirementDate = $user->retirementDate();
        if ($computedRetirementDate) {
            $user->update(['date_of_retirement' => $computedRetirementDate->toDateString()]);
        }

        $request->attributes->set('audit_after', $this->auditSnapshot($user->fresh()));

        $this->notifyPersonnelAndAdmins(
            $user,
            'personnel_update',
            'Personnel information updated',
            trim("Personnel record updated for {$user->surname} {$user->first_name}.")
        );
        if ((int) $oldOfficeId !== (int) $officeId) {
            $oldOfficeName = $oldOfficeId ? (string) Office::whereKey($oldOfficeId)->value('name') : 'Not assigned';
            $newOfficeName = $officeId ? (string) Office::whereKey($officeId)->value('name') : 'Not assigned';
            $this->notifyPersonnelAndAdmins(
                $user,
                'posting',
                'Posting changed',
                trim("Office posting changed for {$user->surname} {$user->first_name}: {$oldOfficeName} → {$newOfficeName}.")
            );
        }

        return redirect()->route('personnel.index')->with('success', 'Personnel updated successfully.');
    }

    public function getLgas(State $state)
    {
        return response()->json($state->lgas()->orderBy('name')->get());
    }

    public function getOffices(Formation $formation)
    {
        return response()->json($formation->offices()->orderBy('name')->get());
    }

    public function updatePhoto(Request $request)
    {
        $user = $request->user();

        $request->validate([
            'photo' => ['required', 'image', 'mimes:jpg,jpeg,png', 'max:5120'],
        ]);

        $request->attributes->set('audit_before', ['photo_path' => (string) ($user->photo_path ?? '')]);

        $photoPath = $this->storePassportPhoto($request->file('photo'), $user->id, $user->photo_path);
        if (! $photoPath) {
            return back()->with('error', 'Unable to upload photograph.');
        }

        $user->update(['photo_path' => $photoPath]);

        $request->attributes->set('audit_after', ['photo_path' => (string) ($user->fresh()->photo_path ?? '')]);

        return redirect()->route('dashboard')->with('success', 'Photograph updated successfully.');
    }

    public function dashboard()
    {
        $user = auth()->user();

        $dashboardMode = session('dashboard_mode', 'personal');
        $canUseAdminMode = ! $user->isUser();
        $canUsePersonalMode = $user->isUser() || $user->isPersonnel();

        if (! $canUseAdminMode) {
            $dashboardMode = 'personal';
        } elseif (! $canUsePersonalMode) {
            $dashboardMode = 'admin';
        } elseif (! in_array($dashboardMode, ['personal', 'admin'], true)) {
            $dashboardMode = 'personal';
        }

        $stats = [];
        $formationHistory = collect();
        $officeHistory = collect();
        $promotionHistory = collect();
        $rankChartLabels = [];
        $rankChartValues = [];
        $currentOfficeHistoryItem = null;
        $missingProfileFields = [];
        $notifications = collect();

        if ($dashboardMode === 'personal') {
            $user->loadMissing('formation');
            $notifications = $user->notifications()->limit(10)->get();
            $formationHistory = $user->formationPostings()->with('formation')->get();
            $allOfficeHistory = $user->officePostings()->with('office')->get();
            if ($user->office_id) {
                $currentOfficeHistoryItem = $allOfficeHistory->firstWhere('office_id', $user->office_id);
            }
            if (! $currentOfficeHistoryItem) {
                $currentOfficeHistoryItem = $allOfficeHistory->first(function ($item) {
                    return strtolower((string) ($item->remark ?? '')) === 'current office';
                });
            }
            $officeHistory = $currentOfficeHistoryItem
                ? $allOfficeHistory->reject(fn ($i) => (int) $i->id === (int) $currentOfficeHistoryItem->id)->values()
                : $allOfficeHistory;
            $promotionHistory = $user->promotions()->get();

            $requiredStrings = [
                'nis_no' => 'NIS No',
                'surname' => 'Surname',
                'first_name' => 'First Name',
                'email' => 'Email',
                'gender' => 'Gender',
                'rank_code' => 'Rank',
            ];
            foreach ($requiredStrings as $key => $label) {
                $value = trim((string) ($user->{$key} ?? ''));
                if ($value === '') {
                    $missingProfileFields[] = $label;
                }
            }

            $requiredDates = [
                'date_of_birth' => 'Date of Birth',
                'date_of_first_appointment' => 'Date of First Appointment',
                'date_of_present_appointment' => 'Date of Present Appointment (DOPA)',
            ];
            foreach ($requiredDates as $key => $label) {
                if (! $user->{$key}) {
                    $missingProfileFields[] = $label;
                }
            }

            $requiredIds = [
                'state_id' => 'State',
                'lga_id' => 'LGA',
                'formation_id' => 'Formation',
            ];
            foreach ($requiredIds as $key => $label) {
                if (! $user->{$key}) {
                    $missingProfileFields[] = $label;
                }
            }

            $formationName = strtolower((string) ($user->formation?->name ?? ''));
            $isServiceHQ = str_contains($formationName, 'service headquarters') || str_contains($formationName, 'shq') || ($user->formation?->code === 'SHQ');
            if ($isServiceHQ && ! $user->directorate_id) {
                $missingProfileFields[] = 'Directorate (for SHQ)';
            }

            if (! $user->photo_path) {
                $missingProfileFields[] = 'Photograph';
            }
        }

        if ($dashboardMode === 'admin' && ! $user->isUser()) {
            $query = User::query();
            if ($user->isOfficeAdmin()) {
                $query->where('office_id', $user->office_id);
            } elseif ($user->isDCG()) {
                $query->where('directorate_id', $user->directorate_id);
            } elseif (! ($user->isMainAdmin() || $user->isSuperAdmin())) {
                $query->where('formation_id', $user->formation_id);
            }

            $personnelRolesToExclude = ['Super Admin', 'Main Admin', 'Formation Admin', 'DCG', 'Principal Staff Officer (PSO)', 'Viewer'];

            $personnelQuery = (clone $query)
                ->where('employment_status', 'Active')
                ->whereNotIn('role', $personnelRolesToExclude);

            $stats['total_personnel'] = (clone $personnelQuery)->count();

            if ($user->isSuperAdmin()) {
                $stats['formations_count'] = Formation::count();
                $stats['offices_count'] = Office::count();
            } elseif ($user->isMainAdmin()) {
                $stats['formations_count'] = Formation::count();
                $stats['offices_count'] = Office::count();
            } else {
                $stats['offices_count'] = Office::where('formation_id', $user->formation_id)->count();
            }

            $counts = (clone $personnelQuery)
                ->whereNotNull('rank_code')
                ->where('rank_code', '<>', '')
                ->selectRaw('rank_code, count(*) as c')
                ->groupBy('rank_code')
                ->pluck('c', 'rank_code')
                ->all();

            $dopa2Years = ['AII', 'IA-1', 'IA-2', 'IA-3'];
            $dopa3Years = ['CSI', 'SI', 'DSI', 'ASI-1', 'ASI-2', 'II'];
            $dopa4Years = ['CG', 'DCG', 'ACG', 'CIS', 'DCI', 'ACI'];
            $twoYearsAgo = Carbon::today()->subYears(2)->toDateString();
            $threeYearsAgo = Carbon::today()->subYears(3)->toDateString();
            $fourYearsAgo = Carbon::today()->subYears(4)->toDateString();

            $stats['due_for_promotion'] = (clone $personnelQuery)
                ->whereNotNull('rank_code')
                ->where('rank_code', '<>', '')
                ->whereNotNull('date_of_present_appointment')
                ->where(function ($q) use ($dopa2Years, $dopa3Years, $dopa4Years, $twoYearsAgo, $threeYearsAgo, $fourYearsAgo) {
                    $q->orWhere(function ($sub) use ($dopa2Years, $twoYearsAgo) {
                        $sub->whereIn('rank_code', $dopa2Years)
                            ->whereDate('date_of_present_appointment', '<=', $twoYearsAgo);
                    })->orWhere(function ($sub) use ($dopa3Years, $threeYearsAgo) {
                        $sub->whereIn('rank_code', $dopa3Years)
                            ->whereDate('date_of_present_appointment', '<=', $threeYearsAgo);
                    })->orWhere(function ($sub) use ($dopa4Years, $fourYearsAgo) {
                        $sub->whereIn('rank_code', $dopa4Years)
                            ->whereDate('date_of_present_appointment', '<=', $fourYearsAgo);
                    });
                })
                ->count();

            $rankOrder = collect(json_decode(File::get(database_path('data/ranks.json')), true))
                ->map(fn ($r) => $r['code'])
                ->values()
                ->all();

            $rankChartLabels = array_keys($counts);
            usort($rankChartLabels, function ($a, $b) use ($rankOrder) {
                $ia = array_search($a, $rankOrder, true);
                $ib = array_search($b, $rankOrder, true);
                $ia = $ia === false ? PHP_INT_MAX : $ia;
                $ib = $ib === false ? PHP_INT_MAX : $ib;

                return $ia <=> $ib;
            });
            $rankChartValues = array_map(fn ($k) => (int) ($counts[$k] ?? 0), $rankChartLabels);
        }

        return view('dashboard', compact('user', 'dashboardMode', 'stats', 'rankChartLabels', 'rankChartValues', 'formationHistory', 'officeHistory', 'promotionHistory', 'currentOfficeHistoryItem', 'missingProfileFields', 'notifications'));
    }

    public function setDashboardMode(Request $request)
    {
        $user = auth()->user();
        if ($user->isUser()) {
            session(['dashboard_mode' => 'personal']);

            return redirect()->route('dashboard');
        }

        $request->validate([
            'mode' => ['required', 'string', 'in:personal,admin'],
        ]);

        session(['dashboard_mode' => $request->mode]);

        return redirect()->route('dashboard');
    }

    private function storePassportPhoto($file, int $userId, ?string $oldPath = null): ?string
    {
        $dir = public_path('uploads/photos');
        File::ensureDirectoryExists($dir);

        if ($oldPath) {
            $oldFull = public_path($oldPath);
            if (is_file($oldFull)) {
                @unlink($oldFull);
            }
        }

        $filename = 'passport_'.$userId.'_'.time().'.jpg';
        $destFullPath = $dir.DIRECTORY_SEPARATOR.$filename;

        $bytes = @file_get_contents($file->getRealPath());
        if ($bytes === false) {
            return null;
        }

        if (! function_exists('imagecreatefromstring')) {
            $file->move($dir, $filename);

            return 'uploads/photos/'.$filename;
        }

        $src = @imagecreatefromstring($bytes);
        if (! $src) {
            $file->move($dir, $filename);

            return 'uploads/photos/'.$filename;
        }

        $srcW = imagesx($src);
        $srcH = imagesy($src);
        if (! $srcW || ! $srcH) {
            imagedestroy($src);

            return null;
        }

        $targetRatio = 7 / 9;
        $srcRatio = $srcW / $srcH;

        $cropW = $srcW;
        $cropH = $srcH;
        $cropX = 0;
        $cropY = 0;

        if ($srcRatio > $targetRatio) {
            $cropW = (int) floor($srcH * $targetRatio);
            $cropX = (int) floor(($srcW - $cropW) / 2);
        } else {
            $cropH = (int) floor($srcW / $targetRatio);
            $cropY = (int) floor(($srcH - $cropH) / 2);
        }

        $outW = 420;
        $outH = 540;
        $dst = imagecreatetruecolor($outW, $outH);
        imagefill($dst, 0, 0, imagecolorallocate($dst, 255, 255, 255));
        imagecopyresampled($dst, $src, 0, 0, $cropX, $cropY, $outW, $outH, $cropW, $cropH);

        imagejpeg($dst, $destFullPath, 90);

        imagedestroy($dst);
        imagedestroy($src);

        return 'uploads/photos/'.$filename;
    }

    private function auditSnapshot(User $user): array
    {
        return $user->only([
            'nis_no',
            'surname',
            'first_name',
            'other_names',
            'email',
            'phone',
            'gender',
            'rank_code',
            'rank',
            'state_id',
            'lga_id',
            'date_of_birth',
            'date_of_first_appointment',
            'date_of_present_appointment',
            'date_of_present_promotion',
            'date_of_present_posting_to_formation',
            'formation_id',
            'office_id',
            'directorate_id',
            'role',
            'employment_status',
            'retired_at',
            'retirement_reason',
            'date_of_retirement',
        ]);
    }

    private function isServiceHqFormation(?Formation $formation): bool
    {
        if (! $formation) {
            return false;
        }

        $code = strtoupper((string) ($formation->code ?? ''));
        if ($code === 'SHQ') {
            return true;
        }

        $name = strtolower((string) ($formation->name ?? ''));
        return str_contains($name, 'service headquarters')
            || str_contains($name, 'headquarters')
            || str_contains($name, 'shq');
    }

    private function notifyPersonnelAndAdmins(User $personnel, string $type, string $title, ?string $body = null, ?array $data = null): void
    {
        $service = new NotificationService();
        $actorId = auth()->id();

        $admins = $service->relatedAdminsForPersonnel($personnel);
        $service->notifyUsers($admins, $title, $body, $actorId, $type, array_merge(['personnel_id' => $personnel->id], $data ?? []));

        $service->notifyUsers([$personnel], $title, $body, $actorId, $type, array_merge(['personnel_id' => $personnel->id], $data ?? []));
    }
}
