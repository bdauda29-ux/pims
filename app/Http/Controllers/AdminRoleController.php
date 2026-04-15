<?php

namespace App\Http\Controllers;

use App\Models\Office;
use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AdminRoleController extends Controller
{
    public function index(Request $request)
    {
        $currentUser = $request->user();

        $query = User::query()
            ->with(['formation', 'office'])
            ->where('employment_status', 'Active')
            ->whereIn('role', ['User', 'Office Admin'])
            ->whereNotNull('rank_code');

        if ($currentUser->isFormationAdmin()) {
            $query->where('formation_id', $currentUser->formation_id);
        } elseif ($currentUser->isDCG() || $currentUser->isPSO()) {
            $query->where('directorate_id', $currentUser->directorate_id ?: -1);
        }

        if ($request->filled('q')) {
            $q = (string) $request->q;
            $query->where(function ($sub) use ($q) {
                $sub->where('surname', 'like', "%{$q}%")
                    ->orWhere('first_name', 'like', "%{$q}%")
                    ->orWhere('other_names', 'like', "%{$q}%")
                    ->orWhere('nis_no', 'like', "%{$q}%")
                    ->orWhere('email', 'like', "%{$q}%");
            });
        }

        $users = $query->orderBy('surname')->paginate(15)->withQueryString();
        $offices = Office::query()
            ->with('formation')
            ->when(
                $currentUser->isFormationAdmin(),
                fn ($q) => $q->where('formation_id', $currentUser->formation_id)
            )
            ->when(
                $currentUser->isDCG() || $currentUser->isPSO(),
                fn ($q) => $q->where('directorate_id', $currentUser->directorate_id ?: -1)
            )
            ->orderBy('name')
            ->get(['id', 'formation_id', 'directorate_id', 'name']);

        return view('admin.roles.index', compact('users', 'offices'));
    }

    public function update(Request $request, User $user)
    {
        $currentUser = $request->user();
        if (! ($currentUser->isMainAdmin() || $currentUser->isSuperAdmin() || $currentUser->isFormationAdmin() || $currentUser->isDCG() || $currentUser->isPSO())) {
            abort(403);
        }

        if (! $user->isPersonnel() || $user->employment_status !== 'Active') {
            abort(404);
        }

        if ($currentUser->isFormationAdmin() && (int) $user->formation_id !== (int) $currentUser->formation_id) {
            abort(403);
        }
        if (($currentUser->isDCG() || $currentUser->isPSO()) && (int) $user->directorate_id !== (int) $currentUser->directorate_id) {
            abort(403);
        }

        $validated = $request->validate([
            'role' => ['required', 'string', Rule::in(['User', 'Office Admin'])],
            'office_id' => ['nullable', 'integer', 'exists:offices,id'],
        ]);

        $request->attributes->set('audit_before', $user->only(['role', 'office_id']));

        $targetRole = $validated['role'];
        $officeId = $validated['office_id'] ?? null;

        if ($targetRole === 'Office Admin') {
            if (! $officeId) {
                return back()->withErrors(['office_id' => 'Please select an office.'])->withInput();
            }

            $officeQuery = Office::query()->whereKey($officeId);
            if ($currentUser->isDCG() || $currentUser->isPSO()) {
                $officeQuery->where('directorate_id', $currentUser->directorate_id ?: -1);
            } else {
                if (! $user->formation_id) {
                    return back()->withErrors(['office_id' => 'Personnel must have a formation before assigning Office Admin.'])->withInput();
                }
                $officeQuery->where('formation_id', $user->formation_id);
            }
            $officeOk = $officeQuery->exists();

            if (! $officeOk) {
                return back()->withErrors(['office_id' => 'Selected office is not allowed for this account.'])->withInput();
            }
        } else {
            $officeId = $user->office_id;
        }

        $user->update([
            'role' => $targetRole,
            'office_id' => $officeId,
        ]);

        $request->attributes->set('audit_after', $user->fresh()->only(['role', 'office_id']));

        $service = new NotificationService();
        $admins = $service->relatedAdminsForPersonnel($user);
        $title = 'Role changed';
        $body = trim("Role updated for {$user->surname} {$user->first_name}: {$targetRole}.");
        $service->notifyUsers($admins, $title, $body, (int) $currentUser->id, 'role_change', ['personnel_id' => $user->id]);
        $service->notifyUsers([$user], $title, $body, (int) $currentUser->id, 'role_change', ['personnel_id' => $user->id]);

        return back()->with('success', 'Role updated successfully.');
    }
}
