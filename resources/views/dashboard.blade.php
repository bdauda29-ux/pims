<x-app-layout>
    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            @if(($dashboardMode ?? 'personal') === 'personal')
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 sm:p-8">
                        <div class="flex flex-col gap-6 sm:flex-row sm:items-center sm:justify-between">
                            <div>
                                <div class="text-3xl font-semibold">
                                    <span class="text-gray-900">Welcome,</span>
                                    <span class="font-bold" style="color: chocolate;">{{ auth()->user()->rank_code }}</span>
                                    <span class="inline-block rounded-md px-3 py-1 font-bold" style="color: chocolate; background-color: #fff7e6; border: 1px solid chocolate;">
                                        {{ auth()->user()->nameTag() }}
                                    </span>
                                </div>
                                @if(!empty($missingProfileFields))
                                    <div class="mt-4 rounded-md border border-red-200 bg-red-50 px-4 py-3">
                                        <div class="text-sm font-semibold text-red-700">Profile incomplete</div>
                                        <div class="mt-1 text-sm text-red-700">Fields that need attention:</div>
                                        <div class="mt-2 flex flex-wrap gap-2">
                                            @foreach($missingProfileFields as $field)
                                                <span class="inline-flex items-center rounded-full bg-red-100 px-3 py-1 text-xs font-semibold text-red-700">
                                                    {{ $field }}
                                                </span>
                                            @endforeach
                                        </div>
                                    </div>
                                @endif
                                @if(isset($notifications) && $notifications->count())
                                    <div class="mt-4 rounded-md border border-gray-200 bg-gray-50 px-4 py-3">
                                        <div class="text-sm font-semibold text-gray-900">Notifications</div>
                                        <div class="mt-2 space-y-2">
                                            @foreach($notifications->take(5) as $note)
                                                <div class="rounded-md px-3 py-2 {{ ($note->type ?? '') === 'rejected' ? 'bg-red-50 border border-red-200' : 'bg-white border border-gray-200' }}">
                                                    <div class="flex items-center justify-between gap-3">
                                                        <div class="text-sm font-semibold {{ ($note->type ?? '') === 'rejected' ? 'text-red-700' : 'text-gray-900' }}">
                                                            {{ $note->title }}
                                                        </div>
                                                        <div class="text-xs text-gray-500 whitespace-nowrap">
                                                            {{ $note->created_at?->format('d/m/Y H:i') }}
                                                        </div>
                                                    </div>
                                                    @if($note->body)
                                                        <div class="mt-1 text-sm {{ ($note->type ?? '') === 'rejected' ? 'text-red-700' : 'text-gray-700' }}">
                                                            {{ $note->body }}
                                                        </div>
                                                    @endif
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                @endif
                                <div class="mt-4 space-y-2 text-sm text-gray-900">
                                    <div>
                                        <span class="font-semibold">Current Office:</span>
                                        {{ auth()->user()->office?->name ?? 'Not assigned' }}
                                        - {{ auth()->user()->formation?->name }}
                                        @if(auth()->user()->date_of_present_posting_to_formation)
                                            (since {{ auth()->user()->date_of_present_posting_to_formation->format('d/m/Y') }})
                                        @endif
                                    </div>
                                    <div>
                                        <span class="font-semibold">Current Formation:</span>
                                        {{ auth()->user()->formation?->name }}
                                        @php
                                            $formationSince = $formationHistory?->first()?->effective_date;
                                        @endphp
                                        @if($formationSince)
                                            (since {{ $formationSince->format('d/m/Y') }})
                                        @endif
                                    </div>
                                    <div>
                                        <span class="font-semibold">Date of Retirement:</span>
                                        {{ auth()->user()->date_of_retirement?->format('d/m/Y') ?? '-' }}
                                    </div>
                                </div>
                            </div>
                            <div class="flex flex-col gap-4 sm:items-end">
                                <div class="flex flex-col gap-2 sm:items-end">
                                    <div class="flex items-center gap-3">
                                        <div class="h-20 w-20 rounded-md border border-gray-200 bg-gray-50 overflow-hidden flex items-center justify-center">
                                            @if(auth()->user()->photo_path)
                                                <img src="{{ asset(auth()->user()->photo_path) }}" alt="Photograph" class="h-full w-full object-cover" />
                                            @else
                                                <div class="text-xs text-gray-500">No Photo</div>
                                            @endif
                                        </div>
                                        <form method="POST" action="{{ route('dashboard.photo') }}" enctype="multipart/form-data" class="flex items-center gap-2">
                                            @csrf
                                            <input name="photo" type="file" accept="image/jpeg,image/png" class="h-10 w-56 border border-gray-300 rounded-md focus:border-indigo-500 focus:ring-indigo-500" required />
                                            <button type="submit" class="h-10 px-4 bg-emerald-600 hover:bg-emerald-700 text-white rounded-md font-semibold">
                                                Upload
                                            </button>
                                        </form>
                                    </div>
                                    @if($errors->any())
                                        <div class="text-sm text-red-700">
                                            {{ $errors->first('photo') }}
                                        </div>
                                    @endif
                                </div>

                                <div class="flex flex-wrap gap-3">
                                    @if(auth()->user()->hasAbility('personnel.edit'))
                                        <a href="{{ route('personnel.edit', auth()->user()) }}" class="inline-flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-md font-semibold">
                                            Edit Information
                                        </a>
                                    @endif
                                    <button type="button" @click="openChangePassword = true" class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md bg-white font-semibold text-gray-700">
                                        Change Password
                                    </button>
                                    <form method="POST" action="{{ route('logout') }}">
                                        @csrf
                                        <button type="submit" class="inline-flex items-center px-4 py-2 border border-red-300 text-red-600 rounded-md bg-white font-semibold">
                                            Logout
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="mt-8 grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div class="p-6">
                            <div class="text-lg font-semibold text-gray-900 mb-4">Formation Posting History</div>
                            <div class="overflow-x-auto">
                                <table class="min-w-full border-collapse">
                                    <thead class="bg-gray-50">
                                        <tr class="text-xs font-semibold text-gray-700">
                                            <th class="px-4 py-3 text-left">Date</th>
                                            <th class="px-4 py-3 text-left">To</th>
                                            <th class="px-4 py-3 text-left">Status</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-200">
                                        @forelse($formationHistory as $item)
                                            <tr class="text-sm">
                                                <td class="px-4 py-3 whitespace-nowrap {{ $loop->first ? 'font-semibold' : '' }}">{{ $item->effective_date?->format('d/m/Y') }}</td>
                                                <td class="px-4 py-3 {{ $loop->first ? 'font-semibold' : '' }}">{{ $item->formation?->name }}</td>
                                                <td class="px-4 py-3 {{ $loop->first ? 'font-semibold' : '' }}">{{ $item->remark }}</td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="3" class="px-4 py-6 text-sm text-gray-500 text-center">No formation history found.</td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div class="p-6">
                            <div class="text-lg font-semibold text-gray-900 mb-4">Promotion History</div>
                            <div class="overflow-x-auto">
                                <table class="min-w-full border-collapse">
                                    <thead class="bg-gray-50">
                                        <tr class="text-xs font-semibold text-gray-700">
                                            <th class="px-4 py-3 text-left">Date</th>
                                            <th class="px-4 py-3 text-left">Details</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-200">
                                        @forelse($promotionHistory as $item)
                                            <tr class="text-sm">
                                                <td class="px-4 py-3 whitespace-nowrap {{ $loop->first ? 'font-semibold' : '' }}">{{ $item->effective_date?->format('d/m/Y') }}</td>
                                                <td class="px-4 py-3 {{ $loop->first ? 'font-semibold' : '' }}">{{ $item->rank_code }} - {{ $item->rank_name }}</td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="2" class="px-4 py-6 text-sm text-gray-500 text-center">No promotion history found.</td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg lg:col-span-2">
                        <div class="p-6">
                            <div class="text-lg font-semibold text-gray-900 mb-4">Internal Posting History</div>
                            <div class="overflow-x-auto">
                                <table class="min-w-full border-collapse">
                                    <thead class="bg-gray-50">
                                        <tr class="text-xs font-semibold text-gray-700">
                                            <th class="px-4 py-3 text-left">Effective Date</th>
                                            <th class="px-4 py-3 text-left">Office</th>
                                            <th class="px-4 py-3 text-left">Status</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-200">
                                        <tr class="text-sm bg-amber-50 font-semibold">
                                            <td class="px-4 py-3 whitespace-nowrap">{{ $currentOfficeHistoryItem?->effective_date?->format('d/m/Y') }}</td>
                                            <td class="px-4 py-3">{{ auth()->user()->office?->name ?? 'Not assigned' }}</td>
                                            <td class="px-4 py-3">Current Office</td>
                                        </tr>
                                        @foreach($officeHistory as $item)
                                            <tr class="text-sm">
                                                <td class="px-4 py-3 whitespace-nowrap">{{ $item->effective_date?->format('d/m/Y') }}</td>
                                                <td class="px-4 py-3">{{ $item->office?->name ?? 'Not assigned' }}</td>
                                                <td class="px-4 py-3">{{ $item->remark }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            @elseif(($dashboardMode ?? 'personal') === 'admin' && isset($stats))
                <div class="mb-6">
                    <div class="text-2xl font-medium text-gray-900">Dashboard</div>
                    <div class="mt-2 border-b border-gray-200"></div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                    <a href="{{ route('personnel.index') }}" class="block group">
                        <div class="bg-blue-600 text-white rounded-xl p-7 min-h-[165px] flex items-start justify-between transition duration-200 ease-out transform group-hover:-translate-y-1 group-hover:shadow-lg">
                            <div class="space-y-5">
                                <div class="text-xs uppercase tracking-widest opacity-90">Total Personnel</div>
                                <div class="text-5xl font-bold leading-none">{{ $stats['total_personnel'] ?? 0 }}</div>
                                <div class="text-sm opacity-95">Click to view list →</div>
                            </div>
                            <div class="opacity-25 pt-1">
                                <svg class="w-16 h-16" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M16 11c1.66 0 3-1.34 3-3s-1.34-3-3-3-3 1.34-3 3 1.34 3 3 3Zm-8 0c1.66 0 3-1.34 3-3S9.66 5 8 5 5 6.34 5 8s1.34 3 3 3Zm0 2c-2.33 0-7 1.17-7 3.5V19h14v-2.5C15 14.17 10.33 13 8 13Zm8 0c-.29 0-.62.02-1 .05 1.2.8 2 1.9 2 3.45V19h6v-2.5c0-2.33-4.67-3.5-7-3.5Z"></path>
                                </svg>
                            </div>
                        </div>
                    </a>
                    @if(auth()->user()->isMainAdmin() || auth()->user()->isSuperAdmin())
                        <a href="{{ route('formations.index') }}" class="block group">
                            <div class="bg-green-700 text-white rounded-xl p-7 min-h-[165px] flex items-start justify-between transition duration-200 ease-out transform group-hover:-translate-y-1 group-hover:shadow-lg">
                                <div class="space-y-5">
                                    <div class="text-xs uppercase tracking-widest opacity-90">Total Formations</div>
                                    <div class="text-5xl font-bold leading-none">{{ $stats['formations_count'] ?? 0 }}</div>
                                    <div class="text-sm opacity-95">View formations →</div>
                                </div>
                                <div class="opacity-25 pt-1">
                                    <div class="w-12 h-16 rounded bg-white/30"></div>
                                </div>
                            </div>
                        </a>
                    @else
                        <a href="{{ route('offices.index') }}" class="block group">
                            <div class="bg-green-700 text-white rounded-xl p-7 min-h-[165px] flex items-start justify-between transition duration-200 ease-out transform group-hover:-translate-y-1 group-hover:shadow-lg">
                                <div class="space-y-5">
                                    @if(auth()->user()->isOfficeAdmin())
                                        <div class="text-xs uppercase tracking-widest opacity-90">Office</div>
                                        <div class="text-2xl font-bold leading-tight">{{ auth()->user()->office?->name ?? 'Not assigned' }}</div>
                                        <div class="text-sm opacity-95">View office →</div>
                                    @else
                                        <div class="text-xs uppercase tracking-widest opacity-90">Total Offices</div>
                                        <div class="text-5xl font-bold leading-none">{{ $stats['offices_count'] ?? 0 }}</div>
                                        <div class="text-sm opacity-95">View offices →</div>
                                    @endif
                                </div>
                                <div class="opacity-25 pt-1">
                                    <div class="w-12 h-16 rounded bg-white/30"></div>
                                </div>
                            </div>
                        </a>
                    @endif
                    @if(auth()->user()->isFormationAdmin() || auth()->user()->isDCG() || auth()->user()->isMainAdmin() || auth()->user()->isSuperAdmin())
                        <a href="#" class="block group">
                            <div class="bg-amber-400 text-gray-900 rounded-xl p-7 min-h-[165px] flex items-start justify-between transition duration-200 ease-out transform group-hover:-translate-y-1 group-hover:shadow-lg">
                                <div class="space-y-5">
                                    <div class="text-xs uppercase tracking-widest opacity-90">Leave Management</div>
                                    <div class="pt-2">
                                        <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5h6l1 2h11v14H3V5Z"></path>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 14l2 2 4-4"></path>
                                        </svg>
                                    </div>
                                    <div class="text-sm opacity-95">Manage leaves →</div>
                                </div>
                                <div class="opacity-25 pt-1">
                                    <svg class="w-16 h-16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3M5 9h14M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                    </svg>
                                </div>
                            </div>
                        </a>
                    @endif
                </div>
                <div class="bg-white rounded-lg shadow-sm overflow-hidden mb-8">
                    <div class="px-6 py-4 border-b border-gray-200">
                        <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                            <div class="text-lg font-semibold text-gray-700">Personnel by Rank</div>
                            @if(!auth()->user()->isUser())
                                <div class="text-sm font-semibold text-gray-700">
                                    Due for Promotion: <span class="text-gray-900">{{ $stats['due_for_promotion'] ?? 0 }}</span>
                                </div>
                            @endif
                        </div>
                    </div>
                    <div class="p-6">
                        <div class="h-[360px]">
                            <canvas id="rankChart"></canvas>
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </div>
</x-app-layout>

@if(($dashboardMode ?? 'personal') === 'admin' && !auth()->user()->isUser() && isset($rankChartLabels) && isset($rankChartValues))
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        const ctx = document.getElementById('rankChart')?.getContext('2d');
        if (ctx) {
            const labels = @json($rankChartLabels);
            const values = @json($rankChartValues);
            const palette = [
                '#2563eb', '#16a34a', '#f59e0b', '#dc2626', '#7c3aed', '#0ea5e9', '#f97316', '#84cc16',
                '#ec4899', '#14b8a6', '#a855f7', '#64748b', '#fb7185', '#22c55e', '#eab308', '#ef4444',
            ];
            const colors = labels.map((_, i) => palette[i % palette.length]);

            new Chart(ctx, {
                type: 'bar',
                data: {
                    labels,
                    datasets: [{
                        data: values,
                        backgroundColor: '#2563eb',
                        borderRadius: 6,
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false },
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: { precision: 0 }
                        }
                    }
                }
            });
        }
    </script>
@endif
