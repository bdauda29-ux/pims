<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Formations') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if(session('success'))
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-4 text-sm text-green-700">
                        {{ session('success') }}
                    </div>
                </div>
            @endif
            @if(session('error'))
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-4 text-sm text-red-700">
                        {{ session('error') }}
                    </div>
                </div>
            @endif

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <div class="flex items-center justify-between gap-4">
                        <div class="text-lg font-semibold text-gray-900">All Formations</div>
                        @if(auth()->user()->isSuperAdmin() || auth()->user()->isMainAdmin())
                            <a href="{{ route('formations.create') }}" class="inline-flex items-center px-4 py-2 bg-[#7B5A2D] hover:bg-[#6b4f27] text-white rounded-md font-semibold">
                                Create Formation
                            </a>
                        @endif
                    </div>

                    <div class="mt-6 overflow-x-auto">
                        <table class="min-w-full border-collapse">
                            <thead class="bg-gray-50">
                                <tr class="text-xs font-semibold text-gray-700">
                                    <th class="px-4 py-3 text-left">Name</th>
                                    <th class="px-4 py-3 text-left">Code</th>
                                    <th class="px-4 py-3 text-left">Type</th>
                                    <th class="px-4 py-3 text-left">Parent</th>
                                    <th class="px-4 py-3 text-left">Action</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200">
                                @forelse($formations as $formation)
                                    <tr class="text-sm">
                                        <td class="px-4 py-3">
                                            <span class="{{ $formation->type !== 'Zonal Command' ? 'pl-6 inline-block' : '' }}">
                                                {{ $formation->name }}
                                            </span>
                                        </td>
                                        <td class="px-4 py-3 text-gray-700">{{ $formation->code }}</td>
                                        <td class="px-4 py-3 text-gray-700">{{ $formation->type }}</td>
                                        <td class="px-4 py-3 text-gray-700">{{ $formation->parent?->name }}</td>
                                        <td class="px-4 py-3">
                                            @if(auth()->user()->isSuperAdmin() || auth()->user()->isMainAdmin())
                                                <div class="flex items-center gap-3">
                                                    <a href="{{ route('formations.edit', $formation) }}" class="text-indigo-600 font-semibold hover:underline">
                                                        Edit
                                                    </a>
                                                    <form method="POST" action="{{ route('formations.destroy', $formation) }}" onsubmit="return confirm('Delete this formation?')">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="text-red-600 font-semibold hover:underline">
                                                            Delete
                                                        </button>
                                                    </form>
                                                </div>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="px-4 py-10 text-sm text-gray-500 text-center italic">No formations found.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-6">
                        {{ $formations->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
