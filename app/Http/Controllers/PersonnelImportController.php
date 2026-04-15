<?php

namespace App\Http\Controllers;

use App\Models\Formation;
use App\Models\FormationPosting;
use App\Models\Lga;
use App\Models\Office;
use App\Models\OfficePosting;
use App\Models\PromotionHistory;
use App\Models\State;
use App\Models\User;
use App\Support\PersonnelExport;
use Dompdf\Dompdf;
use Dompdf\Options;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class PersonnelImportController extends Controller
{
    public function create()
    {
        return view('personnel.import');
    }

    public function store(Request $request)
    {
        $currentUser = auth()->user();

        if (! ($currentUser->isMainAdmin() || $currentUser->isSuperAdmin())) {
            abort(403);
        }

        $request->validate([
            'file' => ['required', 'file', 'mimetypes:text/plain,text/csv,application/csv,text/comma-separated-values,application/vnd.ms-excel'],
        ]);

        $path = $request->file('file')->getRealPath();
        $handle = fopen($path, 'r');
        if ($handle === false) {
            return back()->withErrors(['file' => 'Unable to read uploaded file.']);
        }

        $header = fgetcsv($handle);
        if (! $header) {
            fclose($handle);

            return back()->withErrors(['file' => 'CSV file is empty.']);
        }

        $header = array_map(fn ($h) => Str::of($h)->trim()->lower()->replace(' ', '_')->toString(), $header);
        $created = 0;
        $skipped = 0;
        $errors = [];

        $rankMap = collect(json_decode(file_get_contents(database_path('data/ranks.json')), true))
            ->keyBy('code')
            ->map(fn ($r) => $r['name'])
            ->all();

        while (($row = fgetcsv($handle)) !== false) {
            $data = [];
            foreach ($header as $i => $key) {
                $data[$key] = $row[$i] ?? null;
            }

            $nisNo = trim((string) ($data['nis_no'] ?? ''));
            $email = strtolower(trim((string) ($data['email'] ?? '')));

            if ($nisNo === '' || $email === '') {
                $skipped++;

                continue;
            }

            if (User::where('nis_no', $nisNo)->orWhere('email', $email)->exists()) {
                $skipped++;

                continue;
            }

            $formation = null;
            if (! empty($data['formation_id'])) {
                $formation = Formation::find($data['formation_id']);
            }
            if (! $formation && ! empty($data['formation'])) {
                $formation = Formation::where('name', $data['formation'])->first();
            }
            if (! $formation) {
                $errors[] = "Row for {$nisNo}: formation not found.";
                $skipped++;

                continue;
            }

            $state = null;
            if (! empty($data['state'])) {
                $state = State::where('name', $data['state'])->first();
            }
            if (! $state) {
                $errors[] = "Row for {$nisNo}: state not found.";
                $skipped++;

                continue;
            }

            $lga = null;
            if (! empty($data['lga'])) {
                $lga = Lga::where('state_id', $state->id)->where('name', $data['lga'])->first();
            }
            if (! $lga) {
                $errors[] = "Row for {$nisNo}: LGA not found for state {$state->name}.";
                $skipped++;

                continue;
            }

            $officeId = null;
            if (! empty($data['office']) && strtolower(trim($data['office'])) !== 'not assigned') {
                $office = Office::where('formation_id', $formation->id)->where('name', $data['office'])->first();
                $officeId = $office?->id;
            }

            $rankCode = trim((string) ($data['rank_code'] ?? ''));
            $rankName = $rankMap[$rankCode] ?? null;

            $role = trim((string) ($data['role'] ?? 'User'));
            if (! in_array($role, ['Formation Admin', 'Office Admin', 'User', 'DCG', 'Principal Staff Officer (PSO)', 'Viewer'], true)) {
                $role = 'User';
            }

            if (in_array($role, ['Principal Staff Officer (PSO)', 'Viewer'], true)) {
                $exists = User::where('role', $role)->exists();
                if ($exists) {
                    $errors[] = "Row for {$nisNo}: role {$role} already assigned.";
                    $skipped++;

                    continue;
                }
            }

            if ($role === 'Formation Admin') {
                $exists = User::where('role', 'Formation Admin')->where('formation_id', $formation->id)->exists();
                if ($exists) {
                    $errors[] = "Row for {$nisNo}: Formation Admin already exists for {$formation->name}.";
                    $skipped++;

                    continue;
                }
            }

            try {
                $user = User::create([
                    'name' => trim(($data['surname'] ?? '').' '.($data['first_name'] ?? '')),
                    'email' => $email,
                    'password' => Hash::make($nisNo),
                    'must_change_password' => true,
                    'nis_no' => $nisNo,
                    'surname' => $data['surname'] ?? '',
                    'first_name' => $data['first_name'] ?? '',
                    'other_names' => $data['other_names'] ?? null,
                    'phone' => $data['phone'] ?? null,
                    'gender' => $data['gender'] ?? null,
                    'rank' => $rankName,
                    'rank_code' => $rankCode ?: null,
                    'date_of_birth' => $data['date_of_birth'] ?? null,
                    'state_id' => $state->id,
                    'lga_id' => $lga->id,
                    'formation_id' => $formation->id,
                    'office_id' => $officeId,
                    'date_of_first_appointment' => $data['date_of_first_appointment'] ?? null,
                    'date_of_present_appointment' => $data['date_of_present_appointment'] ?? null,
                    'date_of_present_posting_to_formation' => $data['date_of_present_posting_to_formation'] ?? null,
                    'nok_name' => $data['nok_name'] ?? null,
                    'nok_phone' => $data['nok_phone'] ?? null,
                    'qualification' => $data['qualification'] ?? null,
                    'field_of_study' => $data['field_of_study'] ?? null,
                    'salary_account_number' => $data['salary_account_number'] ?? null,
                    'bank' => $data['bank'] ?? null,
                    'pfa_number' => $data['pfa_number'] ?? null,
                    'pfa_name' => $data['pfa_name'] ?? null,
                    'nhf_no' => $data['nhf_no'] ?? null,
                    'ippis_no' => $data['ippis_no'] ?? null,
                    'remark' => $data['remark'] ?? null,
                    'role' => $role,
                    'employment_status' => 'Active',
                ]);

                $effectiveFormationDate = $user->date_of_present_posting_to_formation
                    ? $user->date_of_present_posting_to_formation->toDateString()
                    : (isset($user->date_of_present_appointment) ? Carbon::parse($user->date_of_present_appointment)->toDateString() : Carbon::today()->toDateString());

                FormationPosting::create([
                    'user_id' => $user->id,
                    'formation_id' => $formation->id,
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
                    'effective_date' => isset($user->date_of_present_appointment) ? Carbon::parse($user->date_of_present_appointment)->toDateString() : $effectiveFormationDate,
                    'remark' => 'Current Rank',
                ]);

                $computedRetirementDate = $user->retirementDate();
                if ($computedRetirementDate) {
                    $user->update(['date_of_retirement' => $computedRetirementDate->toDateString()]);
                }

                $created++;
            } catch (\Throwable $e) {
                $errors[] = "Row for {$nisNo}: ".$e->getMessage();
                $skipped++;
            }
        }

        fclose($handle);

        return redirect()->route('personnel.index')->with('success', "Import complete. Created {$created}, skipped {$skipped}.");
    }

    public function export(Request $request)
    {
        $currentUser = auth()->user();
        $format = strtolower((string) $request->get('format', 'csv'));
        $mergeName = $request->boolean('merge_name');
        $fields = PersonnelExport::normalizeFields((array) $request->input('fields', []), $mergeName);

        $query = User::with(['formation', 'office', 'state', 'lga', 'directorate'])
            ->where('employment_status', 'Active')
            ->orderBy('surname');
        $query->whereNotIn('role', ['Super Admin', 'Main Admin', 'Formation Admin', 'Principal Staff Officer (PSO)', 'Viewer']);

        if ($currentUser->isOfficeAdmin()) {
            $query->where('office_id', $currentUser->office_id);
        } elseif ($currentUser->isDCG()) {
            $query->where('directorate_id', $currentUser->directorate_id);
        } elseif (! ($currentUser->isMainAdmin() || $currentUser->isSuperAdmin())) {
            $query->where('formation_id', $currentUser->formation_id);
        }

        if ($request->filled('q')) {
            $q = $request->q;
            $query->where(function ($sub) use ($q) {
                $sub->where('surname', 'like', "%{$q}%")
                    ->orWhere('first_name', 'like', "%{$q}%")
                    ->orWhere('other_names', 'like', "%{$q}%")
                    ->orWhere('nis_no', 'like', "%{$q}%")
                    ->orWhere('email', 'like', "%{$q}%");
            });
        }

        if ($request->filled('gender')) {
            $query->where('gender', $request->gender);
        }

        if ($request->filled('rank_code')) {
            $query->where('rank_code', $request->rank_code);
        }

        if (($currentUser->isMainAdmin() || $currentUser->isSuperAdmin()) && $request->filled('formation_id')) {
            $query->where('formation_id', $request->formation_id);
        }

        if ($request->filled('directorate_id')) {
            $query->where('directorate_id', $request->directorate_id);
        }

        if ($request->filled('office_id')) {
            $query->where('office_id', $request->office_id);
        }

        if ($request->filled('dopa')) {
            $query->whereDate('date_of_present_appointment', $request->dopa);
        }

        if ($request->filled('dopp')) {
            $query->whereDate('date_of_present_posting_to_formation', $request->dopp);
        }

        $labels = PersonnelExport::labelsWithCustomFields();
        $headers = array_map(fn ($f) => $labels[$f] ?? $f, $fields);

        $query->with(['customFieldValues.field']);

        $metaLines = [];
        $filenameParts = [];

        $rankCode = trim((string) $request->get('rank_code', ''));
        if ($rankCode !== '') {
            $metaLines[] = 'Rank: '.$rankCode;
            $filenameParts[] = 'rank_'.$rankCode;
        }

        $officeName = null;
        $directorateName = null;
        $formationLabel = null;

        if ($request->filled('office_id')) {
            $office = Office::with(['formation', 'directorate'])->find($request->office_id);
            $officeName = $office?->name;
            $directorateName = $office?->directorate?->name;
            $formationLabel = $office?->formation?->code === 'SHQ'
                ? 'SHQ'
                : ($office?->formation?->name ?? null);
        } elseif ($request->filled('directorate_id')) {
            $directorate = \App\Models\Directorate::find($request->directorate_id);
            $directorateName = $directorate?->name;
        }

        if ($request->filled('formation_id')) {
            $formation = Formation::find($request->formation_id);
            $formationLabel = $formation?->code === 'SHQ'
                ? 'SHQ'
                : ($formation?->name ?? $formationLabel);
        }

        if ($officeName) {
            $metaLines[] = 'Office: '.$officeName;
            $filenameParts[] = 'office_'.Str::slug($officeName);
        }
        if ($directorateName) {
            $metaLines[] = 'Directorate: '.$directorateName;
            $filenameParts[] = 'directorate_'.Str::slug($directorateName);
        }
        if ($formationLabel) {
            $metaLines[] = 'Formation: '.$formationLabel;
            $filenameParts[] = 'formation_'.Str::slug($formationLabel);
        }

        if ($request->filled('dopa')) {
            $metaLines[] = 'DOPA: '.$request->dopa;
            $filenameParts[] = 'dopa_'.$request->dopa;
        }
        if ($request->filled('dopp')) {
            $metaLines[] = 'DOPP: '.$request->dopp;
            $filenameParts[] = 'dopp_'.$request->dopp;
        }

        $filenameBase = 'NIS';
        if ($filenameParts !== []) {
            $filenameBase .= '_'.implode('_', $filenameParts);
        }
        $generatedAt = now()->format('Y-m-d_His');

        if ($format === 'xls' || $format === 'excel') {
            $filename = $filenameBase.'_'.$generatedAt.'.xls';
            $users = $query->limit(5000)->get();
            $rows = $users->map(function ($u) use ($fields) {
                return array_map(fn ($f) => PersonnelExport::value($u, $f), $fields);
            })->all();

            $generatedOn = now()->format('d/m/Y H:i');
            $html = view('personnel.export-table', compact('headers', 'rows', 'metaLines', 'generatedOn'))->render();

            return response($html, 200, [
                'Content-Type' => 'application/vnd.ms-excel; charset=UTF-8',
                'Content-Disposition' => 'attachment; filename="'.$filename.'"',
            ]);
        }

        if ($format === 'pdf') {
            $filename = $filenameBase.'_'.$generatedAt.'.pdf';
            $users = $query->limit(5000)->get();
            $rows = $users->map(function ($u) use ($fields) {
                return array_map(fn ($f) => PersonnelExport::value($u, $f), $fields);
            })->all();

            $generatedOn = now()->format('d/m/Y H:i');
            $html = view('personnel.export-table', compact('headers', 'rows', 'metaLines', 'generatedOn'))->render();

            $options = new Options;
            $options->set('isHtml5ParserEnabled', true);
            $options->set('isRemoteEnabled', false);

            $dompdf = new Dompdf($options);
            $dompdf->setPaper('a4', 'landscape');
            $dompdf->loadHtml($html, 'UTF-8');
            $dompdf->render();

            return response($dompdf->output(), 200, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'attachment; filename="'.$filename.'"',
            ]);
        }

        $filename = $filenameBase.'_'.$generatedAt.'.csv';

        return response()->streamDownload(function () use ($query, $headers, $fields) {
            $out = fopen('php://output', 'w');
            fputcsv($out, $headers);

            $query->chunk(500, function ($chunk) use ($out, $fields) {
                foreach ($chunk as $u) {
                    fputcsv($out, array_map(fn ($f) => PersonnelExport::value($u, $f), $fields));
                }
            });

            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv']);
    }
}
