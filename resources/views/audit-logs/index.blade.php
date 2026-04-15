<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Audit Logs') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900 space-y-6">
                    <form method="GET" action="{{ route('audit-logs.index') }}" class="grid grid-cols-1 md:grid-cols-4 gap-4">
                        <div>
                            <x-input-label for="user_id" :value="__('User')" />
                            <select id="user_id" name="user_id" class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
                                <option value="">All</option>
                                @foreach($users as $u)
                                    <option value="{{ $u->id }}" {{ (string) request('user_id') === (string) $u->id ? 'selected' : '' }}>
                                        {{ $u->surname }} {{ $u->first_name }} ({{ $u->role }})
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <x-input-label for="route" :value="__('Route Name')" />
                            <x-text-input id="route" name="route" type="text" class="mt-1 block w-full" :value="request('route')" placeholder="e.g. formations.store" />
                        </div>

                        <div class="flex items-end gap-2">
                            <button type="submit" class="h-10 inline-flex items-center px-4 bg-indigo-600 hover:bg-indigo-700 text-white rounded-md font-semibold">
                                Filter
                            </button>
                            <a href="{{ route('audit-logs.index') }}" class="h-10 inline-flex items-center px-4 border border-gray-300 rounded-md bg-white font-semibold text-gray-700">
                                Reset
                            </a>
                        </div>
                    </form>

                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Time</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">User</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Action</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Personnel</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Route</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Decision</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">View</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @forelse($logs as $log)
                                    @php
                                        $subject = $log->meta['subject'] ?? null;
                                        $personnelText = '-';
                                        if (is_array($subject) && ($subject['type'] ?? '') === 'User') {
                                            $personnelText = trim((string) ($subject['name'] ?? ''));
                                            if (!empty($subject['nis_no'])) {
                                                $personnelText = trim($personnelText.' ('.$subject['nis_no'].')');
                                            }
                                        }
                                        $isRejected = !empty($log->meta['rejected']);
                                        $isReviewed = !empty($log->meta['reviewed']);
                                    @endphp
                                    <tr class="text-sm">
                                        <td class="px-4 py-3 whitespace-nowrap text-gray-700">{{ $log->created_at?->format('d/m/Y H:i:s') }}</td>
                                        <td class="px-4 py-3 text-gray-700">
                                            {{ $log->user?->name ?? 'Unknown' }}
                                        </td>
                                        <td class="px-4 py-3 text-gray-700">{{ $log->meta['action'] ?? $log->route_name }}</td>
                                        <td class="px-4 py-3 text-gray-700">{{ $personnelText }}</td>
                                        <td class="px-4 py-3 text-gray-700">{{ $log->route_name }}</td>
                                        <td class="px-4 py-3 text-gray-700 whitespace-nowrap">
                                            @if($isRejected)
                                                <span class="inline-flex items-center rounded-full bg-red-100 px-3 py-1 text-xs font-semibold text-red-700">
                                                    Rejected
                                                </span>
                                            @elseif($isReviewed)
                                                <span class="inline-flex items-center rounded-full bg-emerald-100 px-3 py-1 text-xs font-semibold text-emerald-700">
                                                    Kept
                                                </span>
                                            @else
                                                <span class="inline-flex items-center rounded-full bg-gray-100 px-3 py-1 text-xs font-semibold text-gray-700">
                                                    Kept (auto)
                                                </span>
                                            @endif
                                        </td>
                                        <td class="px-4 py-3 whitespace-nowrap">
                                            <a href="{{ route('audit-logs.show', $log) }}" class="h-9 inline-flex items-center px-3 border border-gray-300 rounded-md bg-white font-semibold text-gray-700">
                                                View
                                            </a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="px-4 py-8 text-sm text-gray-500 text-center">No logs found.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div>
                        {{ $logs->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
