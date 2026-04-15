<?php

namespace App\Http\Controllers;

use App\Models\Directorate;
use App\Models\Formation;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AdminAccountController extends Controller
{
    public function createFormationAdmin(Formation $formation)
    {
        return view('admin-accounts.formation-admin-create', compact('formation'));
    }

    public function storeFormationAdmin(Request $request, Formation $formation)
    {
        $request->validate([
            'password' => ['required', 'string', 'min:6', 'max:255'],
        ]);

        $existing = User::query()
            ->where('role', 'Formation Admin')
            ->where('formation_id', $formation->id)
            ->exists();

        if ($existing) {
            throw ValidationException::withMessages([
                'password' => 'Formation Admin already exists for this formation.',
            ]);
        }

        $username = strtoupper((string) $formation->code);
        if ($username === '') {
            throw ValidationException::withMessages([
                'password' => 'Formation code is required before creating a Formation Admin.',
            ]);
        }

        $nisTaken = User::query()->where('nis_no', $username)->exists();
        if ($nisTaken) {
            throw ValidationException::withMessages([
                'password' => 'Username (formation code) is already taken.',
            ]);
        }

        $email = strtolower($username).'@admin.local';
        $emailTaken = User::query()->where('email', $email)->exists();
        if ($emailTaken) {
            $email = strtolower($username).'.'.uniqid('', true).'@admin.local';
        }

        User::create([
            'name' => $formation->name.' Admin',
            'email' => $email,
            'nis_no' => $username,
            'password' => Hash::make((string) $request->password),
            'must_change_password' => true,
            'role' => 'Formation Admin',
            'formation_id' => $formation->id,
            'employment_status' => 'Active',
        ]);

        return redirect()->route('management.formations.admins', $formation)->with('success', 'Formation Admin created successfully.');
    }

    public function createStandalone(string $role)
    {
        $allowed = ['Principal Staff Officer (PSO)', 'Viewer'];
        if (! in_array($role, $allowed, true)) {
            abort(404);
        }

        return view('admin-accounts.standalone-create', compact('role'));
    }

    public function storeStandalone(Request $request, string $role)
    {
        $allowed = ['Principal Staff Officer (PSO)', 'Viewer'];
        if (! in_array($role, $allowed, true)) {
            abort(404);
        }

        $request->validate([
            'username' => ['required', 'string', 'max:50'],
            'password' => ['required', 'string', 'min:6', 'max:255'],
        ]);

        $existing = User::query()->where('role', $role)->where('employment_status', 'Active')->exists();
        if ($existing) {
            throw ValidationException::withMessages([
                'username' => 'This role is already assigned.',
            ]);
        }

        $username = strtoupper(trim((string) $request->username));
        $nisTaken = User::query()->where('nis_no', $username)->exists();
        if ($nisTaken) {
            throw ValidationException::withMessages([
                'username' => 'Username is already taken.',
            ]);
        }

        $email = strtolower($username).'@admin.local';
        $emailTaken = User::query()->where('email', $email)->exists();
        if ($emailTaken) {
            $email = strtolower($username).'.'.uniqid('', true).'@admin.local';
        }

        User::create([
            'name' => $role,
            'email' => $email,
            'nis_no' => $username,
            'password' => Hash::make((string) $request->password),
            'must_change_password' => true,
            'role' => $role,
            'employment_status' => 'Active',
        ]);

        return redirect()->route('management.standalone-roles')->with('success', 'Standalone user created successfully.');
    }

    public function createDirectorateAdmin(Directorate $directorate)
    {
        return view('admin-accounts.directorate-admin-create', compact('directorate'));
    }

    public function storeDirectorateAdmin(Request $request, Directorate $directorate)
    {
        $request->validate([
            'password' => ['required', 'string', 'min:6', 'max:255'],
        ]);

        $existing = User::query()
            ->where('role', 'DCG')
            ->where('directorate_id', $directorate->id)
            ->exists();

        if ($existing) {
            throw ValidationException::withMessages([
                'password' => 'DCG already exists for this directorate.',
            ]);
        }

        $username = strtoupper((string) $directorate->code);
        if ($username === '') {
            throw ValidationException::withMessages([
                'password' => 'Directorate code is required before creating a DCG.',
            ]);
        }

        $nisTaken = User::query()->where('nis_no', $username)->exists();
        if ($nisTaken) {
            throw ValidationException::withMessages([
                'password' => 'Username (directorate code) is already taken.',
            ]);
        }

        $email = strtolower($username).'@admin.local';
        if (User::query()->where('email', $email)->exists()) {
            $email = strtolower($username).'.'.uniqid('', true).'@admin.local';
        }

        $shqId = Formation::query()
            ->where('code', 'SHQ')
            ->orWhere('name', 'like', '%service headquarters%')
            ->orWhere('name', 'like', '%shq%')
            ->value('id');

        User::create([
            'name' => $directorate->name.' DCG',
            'email' => $email,
            'nis_no' => $username,
            'password' => Hash::make((string) $request->password),
            'must_change_password' => true,
            'role' => 'DCG',
            'formation_id' => $shqId,
            'directorate_id' => $directorate->id,
            'employment_status' => 'Active',
        ]);

        return redirect()->route('management.directorates.admins', $directorate)->with('success', 'Directorate admin created successfully.');
    }
}
