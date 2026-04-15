<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Retire / Deactivate Personnel') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900 space-y-6">
                    <div class="text-sm text-gray-700">
                        <div><span class="font-semibold">NIS No:</span> {{ $user->nis_no }}</div>
                        <div><span class="font-semibold">Name:</span> {{ $user->surname }}, {{ $user->first_name }} {{ $user->other_names }}</div>
                    </div>

                    <form method="POST" action="{{ route('personnel.retire.store', $user->id) }}" class="space-y-6">
                        @csrf

                        <div>
                            <x-input-label for="retirement_reason" :value="__('Reason')" />
                            <select id="retirement_reason" name="retirement_reason" class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm" required>
                                <option value="Retire" {{ old('retirement_reason') == 'Retire' ? 'selected' : '' }}>Retire</option>
                                <option value="Resign" {{ old('retirement_reason') == 'Resign' ? 'selected' : '' }}>Resign</option>
                                <option value="Dismiss" {{ old('retirement_reason') == 'Dismiss' ? 'selected' : '' }}>Dismiss</option>
                                <option value="Other" {{ old('retirement_reason') == 'Other' ? 'selected' : '' }}>Other</option>
                            </select>
                            <x-input-error class="mt-2" :messages="$errors->get('retirement_reason')" />
                        </div>

                        <div>
                            <x-input-label for="other_reason" :value="__('Other Reason (if selected)')" />
                            <x-text-input id="other_reason" name="other_reason" type="text" class="mt-1 block w-full" :value="old('other_reason')" />
                            <x-input-error class="mt-2" :messages="$errors->get('other_reason')" />
                        </div>

                        <div>
                            <x-input-label for="retired_at" :value="__('Effective Date')" />
                            <x-text-input id="retired_at" name="retired_at" type="date" class="mt-1 block w-full" :value="old('retired_at')" />
                            <x-input-error class="mt-2" :messages="$errors->get('retired_at')" />
                        </div>

                        <div class="flex items-center justify-end gap-3">
                            <a href="{{ route('personnel.index') }}" class="h-10 inline-flex items-center px-4 border border-gray-300 rounded-md bg-white font-semibold text-gray-700">
                                Cancel
                            </a>
                            <x-danger-button>
                                {{ __('Move to Inactive') }}
                            </x-danger-button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
