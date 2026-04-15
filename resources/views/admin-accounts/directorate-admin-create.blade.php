@php
    $layout = request()->boolean('modal') ? 'modal-layout' : 'app-layout';
@endphp
<x-dynamic-component :component="$layout">
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Create Directorate Admin') }} - {{ $directorate->name }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="{{ request()->boolean('modal') ? 'max-w-xl' : 'max-w-3xl' }} mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <form method="POST" action="{{ route('management.directorates.admins.store', $directorate) }}" class="space-y-6">
                        @csrf

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <x-input-label for="username" :value="__('Username')" />
                                <x-text-input id="username" type="text" class="mt-1 block w-full bg-gray-50 text-gray-500 cursor-not-allowed" value="{{ strtoupper($directorate->code) }}" readonly />
                            </div>

                            <div>
                                <x-input-label for="password" :value="__('Password')" />
                                <x-text-input id="password" name="password" type="password" class="mt-1 block w-full" required />
                                <x-input-error class="mt-2" :messages="$errors->get('password')" />
                            </div>
                        </div>

                        <div class="flex items-center justify-end gap-3">
                            <a href="{{ route('management.directorates.admins', $directorate) }}" class="h-10 inline-flex items-center px-4 border border-gray-300 rounded-md bg-white font-semibold text-gray-700">
                                Cancel
                            </a>
                            <x-primary-button>
                                {{ __('Create Admin') }}
                            </x-primary-button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-dynamic-component>

