<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Create Formation') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    
                    <form
                        method="POST"
                        action="{{ route('formations.store') }}"
                        class="space-y-8"
                        x-data="{ type: @js(old('formation_type', '')), parentId: @js(old('parent_id', '')) }"
                    >
                        @csrf

                        <div>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <x-input-label for="formation_name" :value="__('Formation Name')" />
                                    <x-text-input id="formation_name" name="formation_name" type="text" class="mt-1 block w-full" :value="old('formation_name')" required autofocus />
                                    <x-input-error class="mt-2" :messages="$errors->get('formation_name')" />
                                </div>

                                <div>
                                    <x-input-label for="formation_code" :value="__('Code')" />
                                    <x-text-input id="formation_code" name="formation_code" type="text" class="mt-1 block w-full" :value="old('formation_code')" required />
                                    <x-input-error class="mt-2" :messages="$errors->get('formation_code')" />
                                </div>

                                <div>
                                    <x-input-label for="formation_type" :value="__('Type')" />
                                    <select id="formation_type" name="formation_type" x-model="type" class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm" required>
                                        <option value="">Select Type</option>
                                        @foreach($types as $t)
                                            <option value="{{ $t }}" {{ old('formation_type') === $t ? 'selected' : '' }}>{{ $t }}</option>
                                        @endforeach
                                    </select>
                                    <x-input-error class="mt-2" :messages="$errors->get('formation_type')" />
                                </div>

                                <div x-show="type && type !== 'Zonal Command'" x-cloak>
                                    <x-input-label for="parent_id" :value="__('Zonal Command (Parent)')" />
                                    <select id="parent_id" name="parent_id" x-model="parentId" :required="type && type !== 'Zonal Command'" class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
                                        <option value="">Select Zonal Command</option>
                                        @foreach($zonalCommands as $zonal)
                                            <option value="{{ $zonal->id }}" {{ (string) old('parent_id') === (string) $zonal->id ? 'selected' : '' }}>
                                                {{ $zonal->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    <x-input-error class="mt-2" :messages="$errors->get('parent_id')" />
                                </div>
                            </div>
                        </div>

                        {{-- SUBMIT BUTTON --}}
                        <div class="flex items-center justify-end mt-6">
                            <x-primary-button class="ms-4 px-6">
                                {{ __('Create Formation') }}
                            </x-primary-button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
