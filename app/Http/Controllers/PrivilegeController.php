<?php

namespace App\Http\Controllers;

use App\Models\RolePermission;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class PrivilegeController extends Controller
{
    private const ROLES = [
        'Super Admin',
        'Main Admin',
        'Formation Admin',
        'Office Admin',
        'DCG',
        'Principal Staff Officer (PSO)',
        'Viewer',
        'User',
    ];

    private const ABILITIES = [
        'personnel.view' => 'View Personnel',
        'personnel.register' => 'Register Personnel',
        'personnel.edit' => 'Edit Personnel',
        'personnel.import' => 'Import Personnel',
        'personnel.export' => 'Export Personnel',
        'formations.manage' => 'Manage Formations',
        'directorates.manage' => 'Manage Directorates',
        'offices.manage' => 'Manage Offices',
        'custom_fields.manage' => 'Manage Custom Fields',
        'roles.manage' => 'Role Management',
        'audit.view' => 'View Audit Log',
        'management.view' => 'View Management',
    ];

    public function index()
    {
        $roles = self::ROLES;
        $abilities = self::ABILITIES;

        $permissions = RolePermission::query()
            ->whereIn('role', $roles)
            ->whereIn('ability', array_keys($abilities))
            ->get()
            ->groupBy('role')
            ->map(fn ($rows) => $rows->pluck('allowed', 'ability')->all())
            ->all();

        return view('privileges.index', compact('roles', 'abilities', 'permissions'));
    }

    public function update(Request $request)
    {
        $roles = self::ROLES;
        $abilities = array_keys(self::ABILITIES);

        $submitted = (array) $request->input('permissions', []);

        foreach ($roles as $role) {
            if ($role === 'Main Admin') {
                continue;
            }

            $rolePerms = (array) ($submitted[$role] ?? []);
            foreach ($abilities as $ability) {
                $allowed = (bool) ($rolePerms[$ability] ?? false);
                RolePermission::updateOrCreate(
                    ['role' => $role, 'ability' => $ability],
                    ['allowed' => $allowed]
                );
            }

            Cache::forget('role_permissions:'.$role);
        }

        return redirect()->route('privileges.index')->with('success', 'Privileges updated successfully.');
    }
}
