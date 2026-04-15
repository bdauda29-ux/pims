<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Offices') }}
            </h2>
            <a href="{{ route('offices.create') }}" class="bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-2 px-4 rounded transition duration-150 ease-in-out">
                + Add Office
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            @if(session('success'))
                <div class="mb-6 bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-4 text-sm text-green-700">
                        {{ session('success') }}
                    </div>
                </div>
            @endif
            @if(session('error'))
                <div class="mb-6 bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-4 text-sm text-red-700">
                        {{ session('error') }}
                    </div>
                </div>
            @endif
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    @php
                        $treeIndentPx = 20;
                    @endphp

                    <div class="space-y-8">
                        @forelse($organogram as $group)
                            @php
                                $formation = $group['formation'];
                                $isServiceHq = (bool) ($group['is_service_hq'] ?? false);
                            @endphp

                            <div>
                                <div class="flex items-center justify-between gap-4">
                                    <h3 class="text-lg font-semibold text-gray-900">
                                        {{ $formation->name }}
                                    </h3>
                                    @if($formation->code)
                                        <span class="text-xs px-2 py-1 rounded bg-indigo-50 text-indigo-700 font-semibold">
                                            {{ $formation->code }}
                                        </span>
                                    @endif
                                </div>

                                <div class="mt-4">
                                    @if($isServiceHq)
                                        <div class="space-y-6">
                                            @foreach(($group['directorates'] ?? []) as $dirGroup)
                                                @php
                                                    $directorate = $dirGroup['directorate'] ?? null;
                                                    $roots = $dirGroup['roots'] ?? [];
                                                @endphp

                                                <div class="rounded-lg border border-gray-200 p-4">
                                                    <div class="flex items-center justify-between gap-4">
                                                        <div class="min-w-0">
                                                            <div class="font-semibold text-gray-900 truncate">
                                                                @if($directorate)
                                                                    {{ $directorate->name }}
                                                                    @if($directorate->code)
                                                                        <span class="text-gray-500 font-normal">({{ $directorate->code }})</span>
                                                                    @endif
                                                                @else
                                                                    {{ __('No Directorate') }}
                                                                @endif
                                                            </div>
                                                            <div class="text-xs text-gray-500">
                                                                {{ __('Organogram') }}
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <div class="mt-4">
                                                        @if(count($roots) > 0)
                                                            @include('offices._tree', ['nodes' => $roots, 'level' => 0, 'indentPx' => $treeIndentPx])
                                                        @else
                                                            <div class="text-sm text-gray-500">{{ __('No offices found.') }}</div>
                                                        @endif
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                    @else
                                        @php $roots = $group['roots'] ?? []; @endphp
                                        @if(count($roots) > 0)
                                            @include('offices._tree', ['nodes' => $roots, 'level' => 0, 'indentPx' => $treeIndentPx])
                                        @else
                                            <div class="text-sm text-gray-500">{{ __('No offices found.') }}</div>
                                        @endif
                                    @endif
                                </div>
                            </div>
                        @empty
                            <div class="text-sm text-gray-500 text-center">No offices found.</div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
