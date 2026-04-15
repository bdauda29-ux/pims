<?php

namespace App\Http\Controllers;

use App\Models\Formation;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AdminUserController extends Controller
{
    public function index()
    {
        $users = User::with('formation')->orderBy('surname')->paginate(15);

        return view('admin.users.index', compact('users'));
    }

    public function promoteToMainAdmin(Request $request, User $user)
    {
        $request->validate([
            'formation_id' => ['required', 'exists:formations,id'],
        ]);

        if ($user->role === 'Super Admin') {
            return back()->withErrors(['role' => 'Super Admin cannot be promoted.']);
        }

        $user->update([
            'role' => 'Main Admin',
            'formation_id' => $request->formation_id,
        ]);

        return back()->with('success', 'User promoted to Main Admin successfully.');
    }

    public function resetPasswordDefault(User $user)
    {
        if ($user->isSuperAdmin()) {
            abort(403);
        }

        $user->update([
            'password' => Hash::make((string) $user->nis_no),
            'must_change_password' => true,
        ]);

        return back()->with('success', 'Password reset to default (NIS No).');
    }
}
