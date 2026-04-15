<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Audit Log') }}
        </h2>
    </x-slot>

    @php
        $meta = (array) ($log->meta ?? []);
        $subject = (array) ($meta['subject'] ?? []);
        $personnelText = '-';
        if (($subject['type'] ?? '') === 'User') {
            $personnelText = trim((string) ($subject['name'] ?? ''));
            if (!empty($subject['nis_no'])) {
                $personnelText = trim($personnelText.' ('.$subject['nis_no'].')');
            }
        }
        $isRejected = !empty($meta['rejected']);
        $isReviewed = !empty($meta['reviewed']);
        $canReject = in_array($log->route_name, ['personnel.promote', 'personnel.update'], true) && !$isRejected;
        $canKeep = !$isRejected;
    @endphp

    <div class="py-12">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if(session('success'))
                <div class="rounded-md bg-green-50 border border-green-200 text-green-700 px-4 py-3 text-sm">
                    {{ session('success') }}
                </div>
            @endif
            @if(session('error'))
                <div class="rounded-md bg-red-50 border border-red-200 text-red-700 px-4 py-3 text-sm">
                    {{ session('error') }}
                </div>
            @endif

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900 space-y-4">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                        <div>
                            <div class="text-xs text-gray-500">Time</div>
                            <div class="font-semibold text-gray-900">{{ $log->created_at?->format('d/m/Y H:i:s') }}</div>
                        </div>
                        <div>
                            <div class="text-xs text-gray-500">User</div>
                            <div class="font-semibold text-gray-900">{{ $log->user?->name ?? 'Unknown' }}</div>
                        </div>
                        <div>
                            <div class="text-xs text-gray-500">Action</div>
                            <div class="font-semibold text-gray-900">{{ $meta['action'] ?? $log->route_name }}</div>
                        </div>
                        <div>
                            <div class="text-xs text-gray-500">Personnel</div>
                            <div class="font-semibold text-gray-900">{{ $personnelText }}</div>
                        </div>
                        <div>
                            <div class="text-xs text-gray-500">Route</div>
                            <div class="font-semibold text-gray-900">{{ $log->route_name }}</div>
                        </div>
                        <div>
                            <div class="text-xs text-gray-500">Decision</div>
                            <div class="font-semibold text-gray-900">
                                @if($isRejected)
                                    Rejected
                                @elseif($isReviewed)
                                    Kept
                                @else
                                    Kept (auto)
                                @endif
                            </div>
                        </div>
                    </div>

                    <div class="flex flex-wrap items-center gap-2">
                        @if($canKeep)
                            <form method="POST" action="{{ route('audit-logs.keep', $log) }}">
                                @csrf
                                <button type="submit" class="h-10 px-4 bg-emerald-600 hover:bg-emerald-700 text-white rounded-md font-semibold">
                                    Keep
                                </button>
                            </form>
                        @endif
                        @if($canReject)
                            <form method="POST" action="{{ route('audit-logs.reject', $log) }}">
                                @csrf
                                <input type="text" name="reason" placeholder="Reason (optional)" class="h-10 px-3 border border-gray-300 rounded-md focus:border-indigo-500 focus:ring-indigo-500" />
                                <button type="submit" class="h-10 px-4 bg-red-600 hover:bg-red-700 text-white rounded-md font-semibold" onclick="return confirm('Reject this change and revert it?')">
                                    Reject
                                </button>
                            </form>
                        @endif
                    </div>
                </div>
            </div>

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <div class="text-sm font-semibold text-gray-900 mb-4">What changed</div>
                    @if(empty($diff))
                        <div class="text-sm text-gray-500">No change details recorded for this entry.</div>
                    @else
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr class="text-xs font-semibold text-gray-700">
                                        <th class="px-4 py-3 text-left">Field</th>
                                        <th class="px-4 py-3 text-left">Before</th>
                                        <th class="px-4 py-3 text-left">After</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-200 text-sm">
                                    @foreach($diff as $field => $pair)
                                        <tr>
                                            <td class="px-4 py-3 font-semibold text-gray-900 whitespace-nowrap">{{ $field }}</td>
                                            <td class="px-4 py-3 text-gray-700">{{ is_array($pair) ? ($pair['before'] ?? '') : '' }}</td>
                                            <td class="px-4 py-3 text-gray-700">{{ is_array($pair) ? ($pair['after'] ?? '') : '' }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
