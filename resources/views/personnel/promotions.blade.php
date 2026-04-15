<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Promotion Management') }}
        </h2>
    </x-slot>

    @php
        $defaultEffectiveDate = now()->startOfYear()->format('Y-m-d');
    @endphp

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <form method="GET" action="{{ route('personnel.promotions') }}" class="flex items-center gap-2 w-full sm:w-auto">
                    <input
                        type="text"
                        name="q"
                        value="{{ request('q') }}"
                        placeholder="Search (Surname, NIS...)"
                        class="h-10 w-full sm:w-96 border border-gray-300 rounded-md focus:border-indigo-500 focus:ring-indigo-500"
                    />
                    <button type="submit" class="h-10 px-4 bg-indigo-600 hover:bg-indigo-700 text-white rounded-md font-semibold">
                        Search
                    </button>
                    <a href="{{ route('personnel.promotions') }}" class="h-10 px-4 border border-gray-300 rounded-md bg-white font-semibold text-gray-700">
                        Reset
                    </a>
                </form>

                <a href="{{ route('personnel.index') }}" class="text-blue-600 font-semibold hover:underline">
                    Back to Personnel
                </a>
            </div>

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    @if(session('success'))
                        <div class="mb-4 rounded-md bg-green-50 border border-green-200 text-green-700 px-4 py-3 text-sm">
                            {{ session('success') }}
                        </div>
                    @endif
                    @if(session('error'))
                        <div class="mb-4 rounded-md bg-red-50 border border-red-200 text-red-700 px-4 py-3 text-sm">
                            {{ session('error') }}
                        </div>
                    @endif

                    <div class="overflow-x-auto">
                        <table class="min-w-full border-collapse">
                            <thead class="bg-gray-50">
                                <tr class="text-xs font-semibold text-gray-700">
                                    <th class="px-4 py-3 text-left whitespace-nowrap">S/N</th>
                                    <th class="px-4 py-3 text-left whitespace-nowrap">NIS/No</th>
                                    <th class="px-4 py-3 text-left whitespace-nowrap">Name</th>
                                    <th class="px-4 py-3 text-left whitespace-nowrap">Current Rank</th>
                                    <th class="px-4 py-3 text-left whitespace-nowrap">Next Rank</th>
                                    <th class="px-4 py-3 text-left whitespace-nowrap">Current DOPA</th>
                                    <th class="px-4 py-3 text-left whitespace-nowrap">Promote</th>
                                    <th class="px-4 py-3 text-left whitespace-nowrap">Undo</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @forelse($personnel as $index => $person)
                                    @php
                                        $rankIndex = array_search($person->rank_code, $rankOrder, true);
                                        $nextCode = ($rankIndex !== false && $rankIndex > 0) ? $rankOrder[$rankIndex - 1] : null;
                                    @endphp
                                    <tr class="text-sm">
                                        <td class="px-4 py-4 whitespace-nowrap">{{ $personnel->firstItem() + $index }}</td>
                                        <td class="px-4 py-4 whitespace-nowrap text-blue-600 font-semibold">{{ $person->nis_no }}</td>
                                        <td class="px-4 py-4 whitespace-nowrap font-semibold text-gray-900">{{ $person->surname }}, {{ $person->first_name }}</td>
                                        <td class="px-4 py-4 whitespace-nowrap text-gray-700">{{ $person->rank_code ?? '-' }}</td>
                                        <td class="px-4 py-4 whitespace-nowrap text-gray-700">{{ $nextCode ?? '-' }}</td>
                                        <td class="px-4 py-4 whitespace-nowrap text-gray-700">{{ $person->date_of_present_appointment?->format('d/m/Y') ?? '-' }}</td>
                                        <td class="px-4 py-4 whitespace-nowrap">
                                            <button
                                                type="button"
                                                class="h-10 px-4 bg-emerald-600 hover:bg-emerald-700 text-white rounded-md font-semibold disabled:opacity-50"
                                                @disabled(!$nextCode)
                                                data-open-promote
                                                data-action="{{ route('personnel.promote', $person) }}"
                                                data-name="{{ $person->surname }}, {{ $person->first_name }}"
                                            >
                                                Promote
                                            </button>
                                        </td>
                                        <td class="px-4 py-4 whitespace-nowrap">
                                            <form method="POST" action="{{ route('personnel.promote.undo', $person) }}">
                                                @csrf
                                                <button
                                                    type="submit"
                                                    class="h-10 px-4 bg-orange-600 hover:bg-orange-700 text-white rounded-md font-semibold"
                                                    onclick="return confirm('Undo last promotion for this personnel?')"
                                                >
                                                    Undo
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="8" class="px-6 py-10 text-sm text-gray-500 text-center italic">
                                            No personnel available for promotion.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-6">
                        {{ $personnel->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div id="promoteModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/40 p-4">
        <div class="inline-block w-[33.33vw] max-w-[95vw] max-h-[33.33vh] overflow-y-auto rounded-lg bg-white shadow-xl">
            <div class="px-5 py-4 border-b border-gray-200">
                <div class="text-lg font-semibold text-gray-900">Set Effective Date</div>
                <div id="promoteModalName" class="mt-1 text-sm text-gray-600"></div>
            </div>
            <form id="promoteModalForm" method="POST" action="" class="p-5 space-y-4">
                @csrf
                <div>
                    <label for="modal_effective_date" class="block text-sm font-semibold text-gray-700">Effective Date</label>
                    <input id="modal_effective_date" name="effective_date" type="date" value="{{ old('effective_date', $defaultEffectiveDate) }}" class="mt-1 block w-full h-10 border border-gray-300 rounded-md focus:border-indigo-500 focus:ring-indigo-500" required />
                </div>
                <div class="flex items-center justify-end gap-2">
                    <button type="button" id="promoteModalCancel" class="h-10 px-4 border border-gray-300 rounded-md bg-white font-semibold text-gray-700">Cancel</button>
                    <button type="submit" class="h-10 px-4 bg-emerald-600 hover:bg-emerald-700 text-white rounded-md font-semibold">Promote</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        (function () {
            const modal = document.getElementById('promoteModal');
            const form = document.getElementById('promoteModalForm');
            const nameEl = document.getElementById('promoteModalName');
            const cancelBtn = document.getElementById('promoteModalCancel');

            function closeModal() {
                modal.classList.add('hidden');
                modal.classList.remove('flex');
            }

            document.querySelectorAll('[data-open-promote]').forEach((btn) => {
                btn.addEventListener('click', () => {
                    const action = btn.getAttribute('data-action') || '';
                    const name = btn.getAttribute('data-name') || '';
                    form.setAttribute('action', action);
                    nameEl.textContent = name;
                    modal.classList.remove('hidden');
                    modal.classList.add('flex');
                });
            });

            cancelBtn.addEventListener('click', closeModal);
            modal.addEventListener('click', (e) => {
                if (e.target === modal) closeModal();
            });
        })();
    </script>
</x-app-layout>
