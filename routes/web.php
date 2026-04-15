<?php

use App\Http\Controllers\AdminAccountController;
use App\Http\Controllers\AdminPortalController;
use App\Http\Controllers\AdminRoleController;
use App\Http\Controllers\AdminUserController;
use App\Http\Controllers\AuditLogController;
use App\Http\Controllers\CustomFieldController;
use App\Http\Controllers\DirectorateController;
use App\Http\Controllers\FormationController;
use App\Http\Controllers\ManagementController;
use App\Http\Controllers\OfficeController;
use App\Http\Controllers\PersonnelController;
use App\Http\Controllers\PersonnelImportController;
use App\Http\Controllers\PrivilegeController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application.
| These routes are loaded by the RouteServiceProvider.
|
*/

// Public Landing Page
Route::get('/', function () {
    if (auth()->check()) {
        return redirect()->route('dashboard');
    }

    return redirect()->route('login');
});

/**
 * Dashboard Route
 * Accessible to all authenticated users.
 */
Route::get('/dashboard', [PersonnelController::class, 'dashboard'])
    ->middleware(['auth', 'force_password_change'])
    ->name('dashboard');

Route::post('/dashboard/mode', [PersonnelController::class, 'setDashboardMode'])
    ->middleware(['auth', 'force_password_change'])
    ->name('dashboard.mode');

/**
 * Authenticated User Routes
 */
Route::middleware(['auth', 'force_password_change', 'audit'])->group(function () {

    // Standard User Profile Management
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::post('/dashboard/photo', [PersonnelController::class, 'updatePhoto'])->name('dashboard.photo');

    /**
     * PERSONNEL MANAGEMENT (Admins Only)
     * Accessible to: Main Admin, Formation Admin, Office Admin
     */
    Route::middleware('role:Main Admin,Formation Admin,Office Admin,DCG,Principal Staff Officer (PSO),Viewer')->group(function () {
        // List all accessible personnel
        Route::get('/personnel', [PersonnelController::class, 'index'])->middleware('ability:personnel.view')->name('personnel.index');
        Route::get('/personnel/inactive', [PersonnelController::class, 'inactive'])->middleware('ability:personnel.view')->name('personnel.inactive');
        Route::get('/personnel/promotions', [PersonnelController::class, 'promotions'])->middleware(['role:Main Admin', 'ability:personnel.edit'])->name('personnel.promotions');
        Route::post('/personnel/{user}/promote', [PersonnelController::class, 'promote'])->middleware(['role:Main Admin', 'ability:personnel.edit'])->name('personnel.promote');
        Route::post('/personnel/{user}/promote/undo', [PersonnelController::class, 'undoPromotion'])->middleware(['role:Main Admin', 'ability:personnel.edit'])->name('personnel.promote.undo');
        // Show registration form
        Route::get('/personnel/create', [PersonnelController::class, 'create'])->middleware('ability:personnel.register')->name('personnel.create');
        // Handle registration submission
        Route::post('/personnel', [PersonnelController::class, 'store'])->middleware('ability:personnel.register')->name('personnel.store');
        Route::get('/personnel/{user}', [PersonnelController::class, 'show'])->middleware('ability:personnel.view')->name('personnel.show');
        Route::get('/personnel/{user}/edit', [PersonnelController::class, 'edit'])->middleware('ability:personnel.edit')->name('personnel.edit');
        Route::patch('/personnel/{user}', [PersonnelController::class, 'update'])->middleware('ability:personnel.edit')->name('personnel.update');
        Route::get('/personnel/{user}/retire', [PersonnelController::class, 'retireForm'])->name('personnel.retire');
        Route::post('/personnel/{user}/retire', [PersonnelController::class, 'retireStore'])->name('personnel.retire.store');

        Route::get('/personnel/import', [PersonnelImportController::class, 'create'])->middleware('ability:personnel.import')->name('personnel.import');
        Route::post('/personnel/import', [PersonnelImportController::class, 'store'])->middleware('ability:personnel.import')->name('personnel.import.store');
        Route::get('/personnel/export', [PersonnelImportController::class, 'export'])->middleware('ability:personnel.export')->name('personnel.export');
    });

    /**
     * SUPER ADMIN: Manage and promote users to Main Admin
     */
    Route::middleware('role:Super Admin')->group(function () {
        Route::get('/admin/users', [AdminUserController::class, 'index'])->name('admin.users.index');
        Route::patch('/admin/users/{user}/promote', [AdminUserController::class, 'promoteToMainAdmin'])->name('admin.users.promote');
        Route::patch('/admin/users/{user}/reset-password', [AdminUserController::class, 'resetPasswordDefault'])->name('admin.users.reset-password');
    });

    Route::middleware('role:Super Admin')->group(function () {
        Route::get('/admin/portal', [AdminPortalController::class, 'index'])->name('admin.portal');
    });

    Route::middleware(['role:Main Admin', 'ability:management.view'])->group(function () {
        Route::get('/management', [ManagementController::class, 'index'])->name('management.index');
        Route::get('/management/standalone-roles', [ManagementController::class, 'standaloneRoles'])->name('management.standalone-roles');
        Route::get('/management/standalone/{role}/create', [AdminAccountController::class, 'createStandalone'])->name('management.standalone.create');
        Route::post('/management/standalone/{role}', [AdminAccountController::class, 'storeStandalone'])->name('management.standalone.store');
        Route::get('/management/formations/{formation}/admins', [ManagementController::class, 'formationAdmins'])->name('management.formations.admins');
        Route::get('/management/formations/{formation}/admins/create', [AdminAccountController::class, 'createFormationAdmin'])->name('management.formations.admins.create');
        Route::post('/management/formations/{formation}/admins', [AdminAccountController::class, 'storeFormationAdmin'])->name('management.formations.admins.store');
        Route::get('/management/directorates/{directorate}/admins', [ManagementController::class, 'directorateAdmins'])->name('management.directorates.admins');
        Route::get('/management/directorates/{directorate}/admins/create', [AdminAccountController::class, 'createDirectorateAdmin'])->name('management.directorates.admins.create');
        Route::post('/management/directorates/{directorate}/admins', [AdminAccountController::class, 'storeDirectorateAdmin'])->name('management.directorates.admins.store');
        Route::get('/management/formations/{formation}/rank-chart', [ManagementController::class, 'formationRankChart'])->name('management.formations.rank-chart');
        Route::get('/management/directorates/{directorate}/rank-chart', [ManagementController::class, 'directorateRankChart'])->name('management.directorates.rank-chart');
        Route::post('/management/users/{user}/reset-password', [ManagementController::class, 'resetPassword'])->name('management.users.reset-password');
        Route::delete('/management/users/{user}', [ManagementController::class, 'deleteAdmin'])->name('management.users.delete');
    });

    Route::middleware(['role:Main Admin,Formation Admin,DCG,Principal Staff Officer (PSO)', 'ability:offices.manage'])->group(function () {
        Route::get('/admin/roles', [AdminRoleController::class, 'index'])->name('admin.roles.index');
        Route::patch('/admin/roles/{user}', [AdminRoleController::class, 'update'])->name('admin.roles.update');
    });

    Route::middleware(['role:Main Admin', 'ability:roles.manage'])->group(function () {
        Route::post('/custom-fields', [CustomFieldController::class, 'store'])->middleware('ability:custom_fields.manage')->name('custom-fields.store');
        Route::get('/custom-fields/{customField}/edit', [CustomFieldController::class, 'edit'])->middleware('ability:custom_fields.manage')->name('custom-fields.edit');
        Route::patch('/custom-fields/{customField}', [CustomFieldController::class, 'update'])->middleware('ability:custom_fields.manage')->name('custom-fields.update');
        Route::delete('/custom-fields/{customField}', [CustomFieldController::class, 'destroy'])->middleware('ability:custom_fields.manage')->name('custom-fields.destroy');
    });

    Route::middleware(['role:Main Admin', 'ability:roles.manage'])->group(function () {
        Route::get('/privileges', [PrivilegeController::class, 'index'])->name('privileges.index');
        Route::patch('/privileges', [PrivilegeController::class, 'update'])->name('privileges.update');
    });

    Route::middleware(['role:Main Admin', 'ability:audit.view'])->group(function () {
        Route::get('/audit-logs', [AuditLogController::class, 'index'])->name('audit-logs.index');
        Route::get('/audit-logs/{auditLog}', [AuditLogController::class, 'show'])->name('audit-logs.show');
        Route::post('/audit-logs/{auditLog}/keep', [AuditLogController::class, 'keep'])->name('audit-logs.keep');
        Route::post('/audit-logs/{auditLog}/reject', [AuditLogController::class, 'reject'])->name('audit-logs.reject');
    });

    /**
     * FORMATION MANAGEMENT (Main Admin Only)
     * Only the Superuser (Main Admin) can create new formations.
     */
    Route::middleware('role:Super Admin,Main Admin')->group(function () {
        Route::get('/formations', [FormationController::class, 'index'])->middleware('ability:formations.manage')->name('formations.index');
    });

    Route::middleware('role:Super Admin,Main Admin')->group(function () {
        Route::get('/formations/create', [FormationController::class, 'create'])->middleware('ability:formations.manage')->name('formations.create');
        Route::post('/formations', [FormationController::class, 'store'])->middleware('ability:formations.manage')->name('formations.store');
        Route::get('/formations/{formation}/edit', [FormationController::class, 'edit'])->middleware('ability:formations.manage')->name('formations.edit');
        Route::patch('/formations/{formation}', [FormationController::class, 'update'])->middleware('ability:formations.manage')->name('formations.update');
        Route::delete('/formations/{formation}', [FormationController::class, 'destroy'])->middleware('ability:formations.manage')->name('formations.destroy');

        Route::get('/directorates', [DirectorateController::class, 'index'])->middleware('ability:directorates.manage')->name('directorates.index');
        Route::get('/directorates/create', [DirectorateController::class, 'create'])->middleware('ability:directorates.manage')->name('directorates.create');
        Route::post('/directorates', [DirectorateController::class, 'store'])->middleware('ability:directorates.manage')->name('directorates.store');
        Route::get('/directorates/{directorate}/edit', [DirectorateController::class, 'edit'])->middleware('ability:directorates.manage')->name('directorates.edit');
        Route::patch('/directorates/{directorate}', [DirectorateController::class, 'update'])->middleware('ability:directorates.manage')->name('directorates.update');
    });

    Route::middleware('role:Main Admin,Formation Admin,DCG,Principal Staff Officer (PSO)')->group(function () {
        Route::get('/offices', [OfficeController::class, 'index'])->middleware('ability:offices.manage')->name('offices.index');
        Route::get('/offices/create', [OfficeController::class, 'create'])->middleware('ability:offices.manage')->name('offices.create');
        Route::post('/offices', [OfficeController::class, 'store'])->middleware('ability:offices.manage')->name('offices.store');
        Route::delete('/offices/{office}', [OfficeController::class, 'destroy'])->middleware('ability:offices.manage')->name('offices.destroy');
    });

    /**
     * API ROUTES
     * Used for dynamic front-end functionality (e.g., loading LGAs for a State)
     */
    Route::get('/api/states/{state}/lgas', [PersonnelController::class, 'getLgas'])->name('api.lgas');
    Route::get('/api/formations/{formation}/offices', [PersonnelController::class, 'getOffices'])->name('api.offices');
});

// Load standard Authentication routes (login, register, logout, etc.)
require __DIR__.'/auth.php';
