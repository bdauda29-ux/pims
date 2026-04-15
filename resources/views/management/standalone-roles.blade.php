@php
    $layout = request()->boolean('modal') ? 'modal-layout' : 'app-layout';
@endphp
<x-dynamic-component :component="$layout">
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Role Management') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6" x-data="{ modalOpen: false, modalTitle: '', modalUrl: '', openModal(title, url) { this.modalTitle = title; const hasQuery = String(url || '').includes('?'); this.modalUrl = url + (hasQuery ? '&' : '?') + 'modal=1'; this.modalOpen = true; }, closeModal() { this.modalOpen = false; this.modalTitle = ''; this.modalUrl = ''; } }">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900 space-y-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="border border-gray-200 rounded-lg p-5">
                            <div class="text-sm font-semibold text-gray-900">Principal Staff Officer (PSO)</div>
                            <div class="mt-2 text-sm text-gray-700">
                                @if($pso)
                                    <div class="font-semibold">{{ $pso->name }}</div>
                                    <div class="text-gray-600">{{ $pso->nis_no }} • {{ $pso->email }}</div>
                                @else
                                    <div class="text-gray-500">Not assigned</div>
                                @endif
                            </div>
                            <div class="mt-4">
                                <button type="button" x-on:click="openModal('Assign PSO', '{{ route('management.standalone.create', ['role' => 'Principal Staff Officer (PSO)']) }}')" class="inline-flex items-center px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-md font-semibold">
                                    Assign PSO
                                </button>
                            </div>
                        </div>

                        <div class="border border-gray-200 rounded-lg p-5">
                            <div class="text-sm font-semibold text-gray-900">Viewer</div>
                            <div class="mt-2 text-sm text-gray-700">
                                @if($viewer)
                                    <div class="font-semibold">{{ $viewer->name }}</div>
                                    <div class="text-gray-600">{{ $viewer->nis_no }} • {{ $viewer->email }}</div>
                                @else
                                    <div class="text-gray-500">Not assigned</div>
                                @endif
                            </div>
                            <div class="mt-4">
                                <button type="button" x-on:click="openModal('Assign Viewer', '{{ route('management.standalone.create', ['role' => 'Viewer']) }}')" class="inline-flex items-center px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-md font-semibold">
                                    Assign Viewer
                                </button>
                            </div>
                        </div>
                    </div>

                    <div>
                        <a href="{{ route('management.index') }}" class="text-indigo-600 font-semibold hover:underline">Back to Management</a>
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
