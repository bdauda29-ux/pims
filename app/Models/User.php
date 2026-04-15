<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;

/**
 * The User model represents a Personnel in the system.
 * Every personnel is a user and can log in to see their dashboard.
 */
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * These fields are "mass assignable", meaning they can be filled
     * using the User::create() or $user->fill() methods.
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'nis_no',
        'surname',
        'first_name',
        'other_names',
        'phone',
        'nok_name',
        'nok_phone',
        'qualification',
        'field_of_study',
        'salary_account_number',
        'bank',
        'pfa_number',
        'pfa_name',
        'nhf_no',
        'ippis_no',
        'remark',
        'gender',
        'date_of_birth',
        'state_id',
        'lga_id',
        'date_of_first_appointment',
        'date_of_present_appointment',
        'date_of_present_promotion',
        'date_of_present_posting_to_formation',
        'formation_id',
        'office_id',
        'directorate_id',
        'role', // Roles: 'Main Admin', 'Formation Admin', 'Office Admin', 'User'
        'employment_status',
        'retired_at',
        'retirement_reason',
        'date_of_retirement',
        'must_change_password',
        'rank',
        'rank_code',
        'photo_path',
    ];

    /**
     * These fields are hidden when the user model is converted to an array or JSON.
     * This protects sensitive information like passwords.
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Laravel automatically casts these database fields into PHP objects.
     * For example, 'date_of_birth' will be a Carbon (Date) object in PHP.
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'date_of_birth' => 'date',
            'date_of_first_appointment' => 'date',
            'date_of_present_appointment' => 'date',
            'date_of_present_promotion' => 'date',
            'date_of_present_posting_to_formation' => 'date',
            'retired_at' => 'date',
            'date_of_retirement' => 'date',
            'must_change_password' => 'boolean',
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    | These methods define how the User model connects to other tables.
    */

    /**
     * A user belongs to a State of origin.
     */
    public function state()
    {
        return $this->belongsTo(State::class);
    }

    /**
     * A user belongs to a Local Government Area (LGA).
     */
    public function lga()
    {
        return $this->belongsTo(Lga::class);
    }

    /**
     * A user belongs to a Formation.
     */
    public function formation()
    {
        return $this->belongsTo(Formation::class);
    }

    public function office()
    {
        return $this->belongsTo(Office::class);
    }

    public function directorate()
    {
        return $this->belongsTo(Directorate::class);
    }

    public function formationPostings()
    {
        return $this->hasMany(FormationPosting::class)->orderByDesc('effective_date')->orderByDesc('id');
    }

    public function officePostings()
    {
        return $this->hasMany(OfficePosting::class)->orderByDesc('effective_date')->orderByDesc('id');
    }

    public function promotions()
    {
        return $this->hasMany(PromotionHistory::class)->orderByDesc('effective_date')->orderByDesc('id');
    }

    public function customFieldValues()
    {
        return $this->hasMany(CustomFieldValue::class);
    }

    public function notifications()
    {
        return $this->hasMany(UserNotification::class)->orderByDesc('id');
    }

    /*
    |--------------------------------------------------------------------------
    | Role Helper Methods
    |--------------------------------------------------------------------------
    | These methods make it easy to check a user's role anywhere in the app.
    | Example: if ($user->isMainAdmin()) { ... }
    */

    /**
     * Check if the user is a Main Admin (Superuser).
     */
    public function isMainAdmin()
    {
        return $this->role === 'Main Admin';
    }

    /**
     * Check if the user is the Super Admin.
     */
    public function isSuperAdmin()
    {
        return $this->role === 'Super Admin';
    }

    /**
     * Check if the user is a Formation Admin.
     */
    public function isFormationAdmin()
    {
        return $this->role === 'Formation Admin';
    }

    /**
     * Check if the user is an Office Admin.
     */
    public function isOfficeAdmin()
    {
        return $this->role === 'Office Admin';
    }

    public function isDCG()
    {
        return $this->role === 'DCG';
    }

    public function isPSO()
    {
        return $this->role === 'Principal Staff Officer (PSO)';
    }

    public function isPM()
    {
        return $this->role === 'Provost Marshal (PM)';
    }

    public function isViewer()
    {
        return $this->role === 'Viewer';
    }

    public function hasAbility(string $ability): bool
    {
        if ($this->isMainAdmin()) {
            return true;
        }

        $role = (string) ($this->role ?? '');
        if ($role === '') {
            return false;
        }

        $cacheKey = 'role_permissions:'.$role;
        $allowed = Cache::remember($cacheKey, 300, function () use ($role) {
            return RolePermission::query()
                ->where('role', $role)
                ->pluck('allowed', 'ability')
                ->all();
        });

        return (bool) ($allowed[$ability] ?? false);
    }

    /**
     * Check if the user is a regular Personnel (User).
     */
    public function isUser()
    {
        return $this->role === 'User';
    }

    public function isPersonnel(): bool
    {
        return trim((string) $this->rank_code) !== '';
    }

    public function isActive()
    {
        return $this->employment_status === 'Active';
    }

    public function isInactive()
    {
        return $this->employment_status === 'Inactive';
    }

    public function isCommissionedOfficer()
    {
        $commissioned = ['CG', 'DCG', 'ACG', 'CIS', 'DCI', 'ACI', 'CSI', 'SI', 'DSI', 'ASI-1', 'ASI-2'];

        return in_array($this->rank_code, $commissioned, true);
    }

    public function isNonCommissionedOfficer()
    {
        $nonCommissioned = ['II', 'AII', 'IA-1', 'IA-2', 'IA-3'];

        return in_array($this->rank_code, $nonCommissioned, true);
    }

    public function nameTag()
    {
        $firstName = trim((string) $this->first_name);
        $surname = trim((string) $this->surname);
        $otherNames = trim((string) ($this->other_names ?? ''));

        $otherInitials = collect(preg_split('/\s+/', $otherNames, -1, PREG_SPLIT_NO_EMPTY))
            ->map(fn ($n) => strtoupper(mb_substr($n, 0, 1)))
            ->implode(' ');

        if ($this->isCommissionedOfficer()) {
            $firstInitial = $firstName !== '' ? strtoupper(mb_substr($firstName, 0, 1)) : '';

            return trim(implode(' ', array_filter([$firstInitial, $otherInitials, strtoupper($surname)])));
        }

        return trim(implode(' ', array_filter([$firstName, $otherInitials, strtoupper($surname)])));
    }

    public function retirementDate(): ?Carbon
    {
        if ($this->date_of_birth) {
            $ageDate = Carbon::parse($this->date_of_birth)->addYears(60);
        } else {
            $ageDate = null;
        }

        if ($this->date_of_first_appointment) {
            $serviceDate = Carbon::parse($this->date_of_first_appointment)->addYears(35);
        } else {
            $serviceDate = null;
        }

        $candidates = array_filter([$ageDate, $serviceDate]);
        if (empty($candidates)) {
            return null;
        }

        return collect($candidates)->sort()->first();
    }

    public function rankCadre(): ?string
    {
        $code = $this->rank_code;
        if (! $code) {
            return null;
        }

        $comptroller = ['CG', 'DCG', 'ACG', 'CIS', 'DCI', 'ACI'];
        $superintendent = ['CSI', 'SI', 'DSI', 'ASI-1', 'ASI-2'];
        $inspectorate = ['II', 'AII'];
        $assistant = ['IA-1', 'IA-2', 'IA-3'];

        if (in_array($code, $comptroller, true)) {
            return 'Comptroller Cadre';
        }
        if (in_array($code, $superintendent, true)) {
            return 'Superintendent Cadre';
        }
        if (in_array($code, $inspectorate, true)) {
            return 'Inspectorate Cadre';
        }
        if (in_array($code, $assistant, true)) {
            return 'Assistant Cadre';
        }

        return null;
    }
}
