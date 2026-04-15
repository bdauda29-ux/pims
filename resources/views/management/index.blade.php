@php
    $layout = request()->boolean('modal') ? 'modal-layout' : 'app-layout';
@endphp
<x-dynamic-component :component="$layout">
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Management') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6" x-data="{ modalOpen: false, modalTitle: '', modalUrl: '', openModal(title, url) { this.modalTitle = title; const hasQuery = String(url || '').includes('?'); this.modalUrl = url + (hasQuery ? '&' : '?') + 'modal=1'; this.modalOpen = true; }, closeModal() { this.modalOpen = false; this.modalTitle = ''; this.modalUrl = ''; } }">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">S/N</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Code</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Type</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Personnel</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @php
                                    $sn = 1;
                                    $shqName = $shq?->name ?? 'Service Headquarters';
                                    $shqCode = $shq?->code ?: 'SHQ';
                                @endphp

                                <tr>
                                    <td class="px-4 py-3 text-sm text-gray-900">{{ $sn++ }}</td>
                                    <td class="px-4 py-3 text-sm font-semibold text-gray-900">{{ $shqName }}</td>
                                    <td class="px-4 py-3 text-sm text-gray-700">{{ $shqCode }}</td>
                                    <td class="px-4 py-3 text-sm text-gray-700">Directorate</td>
                                    <td class="px-4 py-3 text-sm text-gray-700">{{ $shqTotalDirectoratePersonnel }}</td>
                                    <td class="px-4 py-3 text-sm">
                                        <div class="flex items-center gap-2">
                                            <button type="button" x-on:click="openModal('Standalone Roles', '{{ route('management.standalone-roles') }}')" class="inline-flex items-center px-3 py-1 border border-gray-300 rounded-md bg-white text-gray-700 hover:bg-gray-50">
                                                Standalone Roles
                                            </button>
                                        </div>
                                    </td>
                                </tr>

                                @foreach($directorates as $directorate)
                                    @php
                                        $count = $directorateCounts[$directorate->id] ?? 0;
                                        $addAdminUrl = $shq
                                            ? route('personnel.create', ['role' => 'DCG', 'formation_id' => $shq->id, 'directorate_id' => $directorate->id])
                                            : route('personnel.create', ['role' => 'DCG', 'directorate_id' => $directorate->id]);
                                    @endphp
                                    <tr class="bg-gray-50/50">
                                        <td class="px-4 py-3 text-sm text-gray-900">{{ $sn++ }}</td>
                                        <td class="px-4 py-3 text-sm text-gray-900">
                                            <span class="mr-2">•</span>{{ $directorate->name }}
                                        </td>
                                        <td class="px-4 py-3 text-sm text-gray-700">{{ $directorate->code }}</td>
                                        <td class="px-4 py-3 text-sm text-gray-700">Directorate</td>
                                        <td class="px-4 py-3 text-sm text-gray-700">{{ $count }}</td>
                                        <td class="px-4 py-3 text-sm">
                                            <div class="flex flex-wrap items-center gap-2">
                                                <button type="button" x-on:click="openModal('Edit Directorate', '{{ route('directorates.edit', $directorate) }}')" class="inline-flex items-center px-3 py-1 border border-gray-300 rounded-md bg-white text-gray-700 hover:bg-gray-50">
                                                    Edit
                                                </button>
                                                <button type="button" x-on:click="openModal('Add Directorate Admin', '{{ route('management.directorates.admins.create', $directorate) }}')" class="inline-flex items-center px-3 py-1 border border-green-300 rounded-md bg-green-50 text-green-700 hover:bg-green-100">
                                                    Add Admin
                                                </button>
                                                <button type="button" x-on:click="openModal('Directorate Admins', '{{ route('management.directorates.admins', $directorate) }}')" class="inline-flex items-center px-3 py-1 border border-blue-300 rounded-md bg-blue-50 text-blue-700 hover:bg-blue-100">
                                                    View Admins
                                                </button>
                                                <button type="button" x-on:click="openModal('Directorate Rank Chart', '{{ route('management.directorates.rank-chart', $directorate) }}')" class="inline-flex items-center px-3 py-1 border border-gray-300 rounded-md bg-white text-gray-700 hover:bg-gray-50">
                                                    Rank Chart
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                                @foreach($formationRows as $row)
                                    @php
                                        $formation = $row['formation'];
                                        $depth = $row['depth'];
                                        $indent = str_repeat('—', (int) $depth);
                                        $count = $formationCounts[$formation->id] ?? 0;
                                    @endphp
                                    @if((int) $depth === 0 && isset($seenZonal) && $seenZonal)
                                        <tr>
                                            <td colspan="6" class="p-0">
                                                <div class="h-2 bg-gray-200"></div>
                                            </td>
                                        </tr>
                                    @endif
                                    @php
                                        if ((int) $depth === 0 && !isset($seenZonal)) {
                                            $seenZonal = true;
                                        }
                                    @endphp
                                    <tr>
                                        <td class="px-4 py-3 text-sm text-gray-900">{{ $sn++ }}</td>
                                        <td class="px-4 py-3 text-sm text-gray-900">
                                            @if($indent !== '')
                                                <span class="mr-2">{{ $indent }}</span>
                                            @endif
                                            {{ $formation->name }}
                                        </td>
                                        <td class="px-4 py-3 text-sm text-gray-700">{{ $formation->code }}</td>
                                        <td class="px-4 py-3 text-sm text-gray-700">{{ $formation->type }}</td>
                                        <td class="px-4 py-3 text-sm text-gray-700">{{ $count }}</td>
                                        <td class="px-4 py-3 text-sm">
                                            <div class="flex flex-wrap items-center gap-2">
                                                <button type="button" x-on:click="openModal('Edit Formation', '{{ route('formations.edit', $formation) }}')" class="inline-flex items-center px-3 py-1 border border-gray-300 rounded-md bg-white text-gray-700 hover:bg-gray-50">
                                                    Edit
                                                </button>
                                                <button type="button" x-on:click="openModal('Add Formation Admin', '{{ route('management.formations.admins.create', $formation) }}')" class="inline-flex items-center px-3 py-1 border border-green-300 rounded-md bg-green-50 text-green-700 hover:bg-green-100">
                                                    Add Admin
                                                </button>
                                                <button type="button" x-on:click="openModal('Formation Admins', '{{ route('management.formations.admins', $formation) }}')" class="inline-flex items-center px-3 py-1 border border-blue-300 rounded-md bg-blue-50 text-blue-700 hover:bg-blue-100">
                                                    View Admins
                                                </button>
                                                <button type="button" x-on:click="openModal('Formation Rank Chart', '{{ route('management.formations.rank-chart', $formation) }}')" class="inline-flex items-center px-3 py-1 border border-gray-300 rounded-md bg-white text-gray-700 hover:bg-gray-50">
                                                    Rank Chart
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div x-show="modalOpen" x-cloak class="fixed inset-0 z-50">
                <div class="absolute inset-0 bg-black/50" x-on:click="closeModal()"></div>
                <div class="absolute inset-0 flex items-center justify-center p-4">
                    <div class="bg-white w-[33.33vw] h-[33.33vh] max-w-[95vw] max-h-[95vh] rounded-lg shadow-lg overflow-hidden">
                        <div class="flex items-center justify-between px-4 py-3 border-b border-gray-200">
                            <div class="font-semibold text-gray-900" x-text="modalTitle"></div>
                            <button type="button" class="text-gray-600 hover:text-gray-900" x-on:click="closeModal()">
                                Close
                            </button>
                        </div>
                        <div class="h-[calc(33.33vh-52px)]">
                            <iframe class="w-full h-full" :src="modalUrl"></iframe>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-dynamic-component>
