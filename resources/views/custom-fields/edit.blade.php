@php
    $layout = request()->boolean('modal') ? 'modal-layout' : 'app-layout';
@endphp
<x-dynamic-component :component="$layout">
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Edit Custom Field') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="{{ request()->boolean('modal') ? 'max-w-3xl' : 'max-w-7xl' }} mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <form method="POST" action="{{ route('custom-fields.update', $customField) }}" class="space-y-6">
                        @csrf
                        @method('PATCH')

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <x-input-label for="label" :value="__('Label')" />
                                <x-text-input id="label" name="label" type="text" class="mt-1 block w-full" :value="old('label', $customField->label)" required autofocus />
                                <x-input-error class="mt-2" :messages="$errors->get('label')" />
                            </div>

                            <div>
                                <x-input-label for="key" :value="__('Key')" />
                                <x-text-input id="key" name="key" type="text" class="mt-1 block w-full" :value="old('key', $customField->key)" required />
                                <x-input-error class="mt-2" :messages="$errors->get('key')" />
                            </div>

                            <div>
                                <x-input-label for="type" :value="__('Type')" />
                                <select id="type" name="type" class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm" required>
                                    @php($t = old('type', $customField->type))
                                    <option value="text" {{ $t === 'text' ? 'selected' : '' }}>Text</option>
                                    <option value="textarea" {{ $t === 'textarea' ? 'selected' : '' }}>Textarea</option>
                                    <option value="number" {{ $t === 'number' ? 'selected' : '' }}>Number</option>
                                    <option value="date" {{ $t === 'date' ? 'selected' : '' }}>Date</option>
                                    <option value="select" {{ $t === 'select' ? 'selected' : '' }}>Select</option>
                                </select>
                                <x-input-error class="mt-2" :messages="$errors->get('type')" />
                            </div>

                            <div class="flex items-center gap-2 mt-7">
                                <input id="required" name="required" type="checkbox" value="1" class="rounded border-gray-300 text-indigo-600" {{ old('required', $customField->required) ? 'checked' : '' }}>
                                <label for="required" class="text-sm text-gray-700">Required</label>
                            </div>

                            <div class="md:col-span-2">
                                <x-input-label for="options" :value="__('Options (for Select)')" />
                                @php($optionsValue = is_array($customField->options) ? implode(',', $customField->options) : '')
                                <textarea id="options" name="options" rows="2" class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm" placeholder="e.g. Yes,No">{{ old('options', $optionsValue) }}</textarea>
                                <x-input-error class="mt-2" :messages="$errors->get('options')" />
                            </div>
                        </div>

                        <div class="flex items-center justify-end gap-3">
                            <a href="{{ route('personnel.index', ['tab' => 'custom']) }}" class="h-10 inline-flex items-center px-4 border border-gray-300 rounded-md bg-white font-semibold text-gray-700">
                                Cancel
                            </a>
                            <x-primary-button>
                                {{ __('Update') }}
                            </x-primary-button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-dynamic-component>
