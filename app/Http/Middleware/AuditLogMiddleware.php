<?php

namespace App\Http\Middleware;

use App\Models\AuditLog;
use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class AuditLogMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if (! $request->user()) {
            return $response;
        }

        if (in_array($request->method(), ['GET', 'HEAD', 'OPTIONS'], true)) {
            return $response;
        }

        if ($request->route()?->getName() === 'logout') {
            return $response;
        }

        $routeName = (string) ($request->route()?->getName() ?? '');
        if ($routeName === '' || Str::startsWith($routeName, 'api.')) {
            return $response;
        }

        if ($response->getStatusCode() >= 400) {
            return $response;
        }

        $allowedRoutes = [
            'profile.update',
            'profile.destroy',
            'dashboard.mode',
            'dashboard.photo',
            'personnel.store',
            'personnel.update',
            'personnel.retire.store',
            'personnel.promote',
            'personnel.promote.undo',
            'personnel.import.store',
            'management.*.store',
            'management.users.reset-password',
            'management.users.delete',
            'admin.users.promote',
            'admin.users.reset-password',
            'admin.roles.update',
            'custom-fields.store',
            'custom-fields.update',
            'custom-fields.destroy',
            'privileges.update',
            'formations.store',
            'formations.update',
            'formations.destroy',
            'directorates.store',
            'directorates.update',
            'offices.store',
            'offices.destroy',
        ];

        $shouldLog = false;
        foreach ($allowedRoutes as $pattern) {
            if (Str::is($pattern, $routeName)) {
                $shouldLog = true;
                break;
            }
        }
        if (! $shouldLog) {
            return $response;
        }

        $subject = $this->extractSubject($request);
        $action = $this->actionLabel($routeName, $request->method());

        $before = $request->attributes->get('audit_before');
        $after = $request->attributes->get('audit_after');
        $diff = $this->diffAssoc($before, $after);

        $meta = [
            'query' => $request->query(),
            'input' => $this->filteredInput($request),
            'status' => $response->getStatusCode(),
            'action' => $action,
            'subject' => $subject,
            'kept' => true,
            'kept_at' => Carbon::now()->toDateTimeString(),
            'kept_by' => $request->user()->id,
        ];
        if (! empty($diff)) {
            $meta['changes'] = [
                'before' => is_array($before) ? $before : null,
                'after' => is_array($after) ? $after : null,
                'diff' => $diff,
            ];
        }

        AuditLog::create([
            'user_id' => $request->user()->id,
            'method' => $request->method(),
            'route_name' => $routeName,
            'path' => $request->path(),
            'ip' => $request->ip(),
            'user_agent' => (string) $request->userAgent(),
            'meta' => $meta,
        ]);

        return $response;
    }

    private function filteredInput(Request $request): array
    {
        $input = $request->except([
            'password',
            'password_confirmation',
            'current_password',
            '_token',
        ]);

        foreach ($input as $k => $v) {
            if (is_string($v) && strlen($v) > 500) {
                $input[$k] = substr($v, 0, 500);
            }
        }

        return $input;
    }

    private function diffAssoc($before, $after): array
    {
        if (! is_array($before) || ! is_array($after)) {
            return [];
        }

        $keys = array_unique(array_merge(array_keys($before), array_keys($after)));
        $diff = [];
        foreach ($keys as $k) {
            $b = $before[$k] ?? null;
            $a = $after[$k] ?? null;

            $bVal = $this->stringify($b);
            $aVal = $this->stringify($a);
            if ($bVal !== $aVal) {
                $diff[$k] = ['before' => $bVal, 'after' => $aVal];
            }
        }

        return $diff;
    }

    private function stringify($value): string
    {
        if ($value instanceof \DateTimeInterface) {
            return Carbon::instance($value)->toDateString();
        }
        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }
        if ($value === null) {
            return '';
        }
        if (is_array($value)) {
            return json_encode($value);
        }

        return trim((string) $value);
    }

    private function actionLabel(string $routeName, string $method): string
    {
        $map = [
            'personnel.store' => 'Personnel created',
            'personnel.update' => 'Personnel updated',
            'personnel.retire.store' => 'Personnel retired',
            'personnel.promote' => 'Personnel promoted',
            'personnel.promote.undo' => 'Promotion undone',
            'personnel.import.store' => 'Personnel import',
            'management.users.reset-password' => 'Password reset',
            'management.users.delete' => 'Admin deleted',
            'admin.users.promote' => 'Promoted to Main Admin',
            'admin.users.reset-password' => 'Password reset',
            'management.standalone.store' => 'Admin created',
            'management.formations.admins.store' => 'Formation admin created',
            'management.directorates.admins.store' => 'Directorate admin created',
            'admin.roles.update' => 'Role updated',
            'custom-fields.store' => 'Custom field created',
            'custom-fields.update' => 'Custom field updated',
            'custom-fields.destroy' => 'Custom field deleted',
            'privileges.update' => 'Privileges updated',
            'formations.store' => 'Formation created',
            'formations.update' => 'Formation updated',
            'formations.destroy' => 'Formation deleted',
            'directorates.store' => 'Directorate created',
            'directorates.update' => 'Directorate updated',
            'offices.store' => 'Office created',
            'offices.destroy' => 'Office deleted',
            'profile.update' => 'Profile updated',
            'profile.destroy' => 'Profile deleted',
            'dashboard.mode' => 'Dashboard mode changed',
            'dashboard.photo' => 'Photograph updated',
        ];

        return $map[$routeName] ?? (strtoupper($method).' '.$routeName);
    }

    private function extractSubject(Request $request): ?array
    {
        $keys = [
            'user' => 'User',
            'formation' => 'Formation',
            'directorate' => 'Directorate',
            'office' => 'Office',
            'customField' => 'Custom Field',
        ];

        foreach ($keys as $key => $type) {
            $value = $request->route($key);
            if (! $value) {
                continue;
            }

            if (is_object($value) && method_exists($value, 'getKey')) {
                $row = [
                    'type' => $type,
                    'id' => $value->getKey(),
                ];
                if ($value instanceof User) {
                    $row['name'] = trim($value->surname.' '.$value->first_name) ?: $value->name;
                    $row['nis_no'] = $value->nis_no;
                } elseif (property_exists($value, 'name')) {
                    $row['name'] = (string) $value->name;
                }

                return $row;
            }

            if (is_numeric($value)) {
                return [
                    'type' => $type,
                    'id' => (int) $value,
                ];
            }
        }

        return null;
    }
}
