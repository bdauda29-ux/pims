<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Privileges') }}
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

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <form method="POST" action="{{ route('privileges.update') }}" class="space-y-6">
                        @csrf
                        @method('PATCH')

                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Role</th>
                                        @foreach($abilities as $abilityKey => $label)
                                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ $label }}</th>
                                        @endforeach
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    @foreach($roles as $role)
                                        <tr>
                                            <td class="px-4 py-3 text-sm font-semibold text-gray-900 whitespace-nowrap">{{ $role }}</td>
                                            @foreach($abilities as $abilityKey => $label)
                                                @php
                                                    $isMain = $role === 'Main Admin';
                                                    $checked = $isMain ? true : (bool) (($permissions[$role][$abilityKey] ?? false));
                                                @endphp
                                                <td class="px-4 py-3 text-sm">
                                                    <label class="inline-flex items-center gap-2">
                                                        <input
                                                            type="checkbox"
                                                            name="permissions[{{ $role }}][{{ $abilityKey }}]"
                                                            value="1"
                                                            class="rounded border-gray-300 text-indigo-600"
                                                            {{ $checked ? 'checked' : '' }}
                                                            {{ $isMain ? 'disabled' : '' }}
                                                        />
                                                        <span class="text-xs text-gray-500">{{ $checked ? 'Yes' : 'No' }}</span>
                                                    </label>
                                                </td>
                                            @endforeach
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        <div class="flex items-center justify-end gap-3">
                            <x-primary-button>
                                {{ __('Save Changes') }}
                            </x-primary-button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>

