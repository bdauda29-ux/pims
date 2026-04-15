<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class AuditLogController extends Controller
{
    public function index(Request $request)
    {
        $query = AuditLog::query()->with('user')->orderByDesc('id');

        if ($request->filled('user_id')) {
            $query->where('user_id', (int) $request->user_id);
        }

        if ($request->filled('route')) {
            $query->where('route_name', 'like', '%'.trim((string) $request->route).'%');
        }

        $logs = $query->paginate(30)->withQueryString();
        $users = User::orderBy('surname')->get(['id', 'name', 'surname', 'first_name', 'role']);

        return view('audit-logs.index', compact('logs', 'users'));
    }

    public function show(AuditLog $auditLog)
    {
        $auditLog->load('user');

        $meta = (array) ($auditLog->meta ?? []);
        $changes = (array) ($meta['changes'] ?? []);
        $diff = (array) ($changes['diff'] ?? []);
        ksort($diff);

        return view('audit-logs.show', [
            'log' => $auditLog,
            'diff' => $diff,
        ]);
    }

    public function keep(AuditLog $auditLog)
    {
        $meta = (array) ($auditLog->meta ?? []);
        if (! empty($meta['rejected'])) {
            return back()->with('error', 'Rejected changes cannot be kept.');
        }

        $meta['reviewed'] = true;
        $meta['reviewed_at'] = Carbon::now()->toDateTimeString();
        $meta['reviewed_by'] = (int) auth()->id();
        $auditLog->update(['meta' => $meta]);

        return redirect()->route('audit-logs.show', $auditLog)->with('success', 'Marked as kept.');
    }

    public function reject(Request $request, AuditLog $auditLog)
    {
        $meta = (array) ($auditLog->meta ?? []);
        if (! empty($meta['rejected'])) {
            return back()->with('error', 'This change has already been rejected.');
        }

        $subject = (array) ($meta['subject'] ?? []);
        $personnelId = isset($subject['id']) ? (int) $subject['id'] : 0;
        if (! $personnelId) {
            return back()->with('error', 'Unable to find personnel for this log.');
        }

        $personnel = User::find($personnelId);
        if (! $personnel) {
            return back()->with('error', 'Personnel record no longer exists.');
        }

        if ($auditLog->route_name === 'personnel.promote') {
            $history = $personnel->promotions()->orderByDesc('effective_date')->orderByDesc('id')->get();
            if ($history->count() < 2) {
                return back()->with('error', 'No promotion record available to undo.');
            }

            $latest = $history->first();
            $previous = $history->get(1);
            if (! $previous) {
                return back()->with('error', 'No previous rank record found.');
            }

            $personnel->update([
                'rank_code' => (string) $previous->rank_code,
                'rank' => (string) $previous->rank_name,
                'date_of_present_appointment' => $previous->effective_date?->toDateString(),
                'date_of_present_promotion' => $previous->effective_date?->toDateString(),
            ]);
            $latest->delete();
        } elseif ($auditLog->route_name === 'personnel.update') {
            $before = (array) (($meta['changes']['before'] ?? []) ?: []);
            if (empty($before)) {
                return back()->with('error', 'No change details available to reject.');
            }

            $allowedKeys = [
                'surname', 'first_name', 'other_names', 'phone',
                'nok_name', 'nok_phone',
                'qualification', 'field_of_study',
                'salary_account_number', 'bank',
                'pfa_number', 'pfa_name',
                'nhf_no', 'ippis_no',
                'gender', 'rank_code', 'rank',
                'date_of_birth',
                'state_id', 'lga_id',
                'date_of_first_appointment',
                'date_of_present_appointment',
                'date_of_present_posting_to_formation',
                'formation_id', 'office_id', 'directorate_id',
                'role',
            ];

            $revert = array_intersect_key($before, array_flip($allowedKeys));
            $personnel->update($revert);
        } else {
            return back()->with('error', 'Reject is not available for this type of change.');
        }

        $meta['rejected'] = true;
        $meta['rejected_at'] = Carbon::now()->toDateTimeString();
        $meta['rejected_by'] = (int) auth()->id();
        $meta['rejected_note'] = trim((string) $request->input('reason', ''));
        $auditLog->update(['meta' => $meta]);

        $service = new NotificationService();
        $admins = $service->relatedAdminsForPersonnel($personnel);
        $reason = $meta['rejected_note'] ?: null;
        $title = 'Change rejected';
        $body = $auditLog->route_name === 'personnel.update'
            ? trim("Your personnel information update was rejected.".($reason ? " Reason: {$reason}" : ''))
            : trim("A promotion change was rejected for {$personnel->surname} {$personnel->first_name}.".($reason ? " Reason: {$reason}" : ''));
        $service->notifyUsers([$personnel], $title, $body, (int) auth()->id(), 'rejected', ['audit_log_id' => $auditLog->id, 'route' => $auditLog->route_name]);
        $service->notifyUsers($admins, $title, $body, (int) auth()->id(), 'rejected', ['audit_log_id' => $auditLog->id, 'route' => $auditLog->route_name, 'personnel_id' => $personnel->id]);

        return back()->with('success', 'Change rejected successfully.');
    }
}
