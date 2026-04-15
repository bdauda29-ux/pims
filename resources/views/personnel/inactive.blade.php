<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Inactive Personnel') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <form method="GET" action="{{ route('personnel.inactive') }}" class="flex items-center gap-2 w-full sm:w-auto">
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
                    <a href="{{ route('personnel.inactive') }}" class="h-10 px-4 border border-gray-300 rounded-md bg-white font-semibold text-gray-700">
                        Reset
                    </a>
                </form>

                <a href="{{ route('personnel.index') }}" class="text-blue-600 font-semibold hover:underline">
                    Back to Active
                </a>
            </div>

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <div class="overflow-x-auto">
                        <table class="min-w-full border-collapse">
                            <thead class="bg-gray-50">
                                <tr class="text-xs font-semibold text-gray-700">
                                    <th class="px-4 py-3 text-left whitespace-nowrap">S/N</th>
                                    <th class="px-4 py-3 text-left whitespace-nowrap">NIS/No</th>
                                    <th class="px-4 py-3 text-left whitespace-nowrap">Name</th>
                                    <th class="px-4 py-3 text-left whitespace-nowrap">Formation</th>
                                    <th class="px-4 py-3 text-left whitespace-nowrap">Office</th>
                                    <th class="px-4 py-3 text-left whitespace-nowrap">Reason</th>
                                    <th class="px-4 py-3 text-left whitespace-nowrap">Retired At</th>
                                    <th class="px-4 py-3 text-left whitespace-nowrap">Retirement Date</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @forelse($personnel as $index => $person)
                                    <tr class="text-sm">
                                        <td class="px-4 py-4 whitespace-nowrap">{{ $personnel->firstItem() + $index }}</td>
                                        <td class="px-4 py-4 whitespace-nowrap text-blue-600 font-semibold">{{ $person->nis_no }}</td>
                                        <td class="px-4 py-4 whitespace-nowrap font-semibold text-gray-900">{{ $person->surname }}, {{ $person->first_name }}</td>
                                        <td class="px-4 py-4 whitespace-nowrap text-gray-700">{{ $person->formation?->name }}</td>
                                        <td class="px-4 py-4 whitespace-nowrap text-gray-700">{{ $person->office?->name ?? 'Not assigned' }}</td>
                                        <td class="px-4 py-4 whitespace-nowrap text-gray-700">{{ $person->retirement_reason }}</td>
                                        <td class="px-4 py-4 whitespace-nowrap text-gray-700">{{ $person->retired_at?->format('d/m/Y') }}</td>
                                        <td class="px-4 py-4 whitespace-nowrap text-gray-700">{{ $person->date_of_retirement?->format('d/m/Y') }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="8" class="px-6 py-10 text-sm text-gray-500 text-center italic">
                                            No inactive personnel found.
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
</x-app-layout>
