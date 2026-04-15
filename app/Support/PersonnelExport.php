<?php

namespace App\Support;

use App\Models\CustomField;
use App\Models\User;

class PersonnelExport
{
    public static function labels(): array
    {
        return [
            'nis_no' => 'NIS',
            'surname' => 'Surname',
            'first_name' => 'First Name',
            'other_names' => 'Other Names',
            'merged_name' => 'Name (Merged)',
            'rank_code' => 'Rank',
            'rank' => 'Rank Name',
            'gender' => 'Gender',
            'date_of_birth' => 'Date of Birth',
            'phone' => 'Phone',
            'email' => 'Email',
            'qualification' => 'Qualification',
            'field_of_study' => 'Field of Study',
            'salary_account_number' => 'Salary Account Number',
            'bank' => 'Bank',
            'pfa_number' => 'PFA Number',
            'pfa_name' => 'PFA Name',
            'nhf_no' => 'NHF No',
            'ippis_no' => 'IPPIS No',
            'remark' => 'Remark',
            'state' => 'State',
            'lga' => 'LGA',
            'formation' => 'Formation',
            'office' => 'Office',
            'date_of_first_appointment' => 'DOFA',
            'date_of_present_appointment' => 'DOPA',
            'date_of_present_posting_to_formation' => 'DOPP',
            'date_of_retirement' => 'Date of Retirement',
        ];
    }

    public static function labelsWithCustomFields(): array
    {
        $labels = self::labels();

        $custom = CustomField::orderBy('label')->get(['key', 'label']);
        foreach ($custom as $f) {
            $labels['cf:'.$f->key] = $f->label;
        }

        return $labels;
    }

    public static function defaultFields(): array
    {
        return [
            'formation',
            'nis_no',
            'surname',
            'first_name',
            'other_names',
            'rank_code',
            'gender',
            'date_of_birth',
            'phone',
            'email',
            'qualification',
            'office',
            'state',
            'lga',
            'date_of_first_appointment',
            'date_of_present_appointment',
            'date_of_present_posting_to_formation',
        ];
    }

    public static function normalizeFields(array $fields, bool $mergeName): array
    {
        $allowed = array_keys(self::labelsWithCustomFields());
        $fields = array_values(array_filter($fields, fn ($f) => in_array($f, $allowed, true)));

        if ($fields === []) {
            $fields = self::defaultFields();
        }

        if ($mergeName) {
            $withoutParts = array_values(array_filter(
                $fields,
                fn ($f) => ! in_array($f, ['surname', 'first_name', 'other_names'], true)
            ));

            if (! in_array('merged_name', $withoutParts, true)) {
                $insertAt = 0;
                foreach ($withoutParts as $idx => $f) {
                    if ($f === 'nis_no') {
                        $insertAt = $idx + 1;
                        break;
                    }
                }
                array_splice($withoutParts, $insertAt, 0, ['merged_name']);
            }

            $fields = $withoutParts;
        }

        return $fields;
    }

    public static function value(User $user, string $field): string
    {
        if (str_starts_with($field, 'cf:')) {
            $key = substr($field, 3);
            $value = $user->customFieldValues
                ->firstWhere('field.key', $key)
                ?->value;

            $value = (string) ($value ?? '');
            if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
                $dt = \DateTime::createFromFormat('Y-m-d', $value);
                if ($dt) {
                    return $dt->format('d/m/Y');
                }
            }

            return $value;
        }

        return match ($field) {
            'nis_no' => (string) $user->nis_no,
            'surname' => (string) $user->surname,
            'first_name' => (string) $user->first_name,
            'other_names' => (string) ($user->other_names ?? ''),
            'merged_name' => (string) $user->nameTag(),
            'rank_code' => (string) ($user->rank_code ?? ''),
            'rank' => (string) ($user->rank ?? ''),
            'gender' => (string) ($user->gender ?? ''),
            'date_of_birth' => $user->date_of_birth?->format('d/m/Y') ?? '',
            'phone' => (string) ($user->phone ?? ''),
            'email' => (string) ($user->email ?? ''),
            'qualification' => (string) ($user->qualification ?? ''),
            'field_of_study' => (string) ($user->field_of_study ?? ''),
            'salary_account_number' => (string) ($user->salary_account_number ?? ''),
            'bank' => (string) ($user->bank ?? ''),
            'pfa_number' => (string) ($user->pfa_number ?? ''),
            'pfa_name' => (string) ($user->pfa_name ?? ''),
            'nhf_no' => (string) ($user->nhf_no ?? ''),
            'ippis_no' => (string) ($user->ippis_no ?? ''),
            'remark' => (string) ($user->remark ?? ''),
            'state' => (string) ($user->state?->name ?? ''),
            'lga' => (string) ($user->lga?->name ?? ''),
            'formation' => (string) (($user->formation?->code === 'SHQ') ? 'SHQ' : ($user->formation?->name ?? '')),
            'office' => (string) ($user->office?->name ?? ''),
            'date_of_first_appointment' => $user->date_of_first_appointment?->format('d/m/Y') ?? '',
            'date_of_present_appointment' => $user->date_of_present_appointment?->format('d/m/Y') ?? '',
            'date_of_present_posting_to_formation' => $user->date_of_present_posting_to_formation?->format('d/m/Y') ?? '',
            'date_of_retirement' => $user->date_of_retirement?->format('d/m/Y') ?? '',
            default => '',
        };
    }
}
