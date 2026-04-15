<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Bulk Import Personnel') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900 space-y-6">
                    <div class="text-sm text-gray-700">
                        Upload a CSV file exported from Excel. Required columns: nis_no, surname, first_name, email, formation, state, lga. Default password is the personnel NIS No.
                    </div>

                    <form method="POST" action="{{ route('personnel.import.store') }}" enctype="multipart/form-data" class="space-y-4">
                        @csrf
                        <div>
                            <x-input-label for="file" :value="__('CSV File')" />
                            <input id="file" name="file" type="file" accept=".csv,text/csv" class="mt-1 block w-full" required />
                            <x-input-error class="mt-2" :messages="$errors->get('file')" />
                        </div>

                        <div class="flex justify-end">
                            <x-primary-button>
                                {{ __('Import') }}
                            </x-primary-button>
                        </div>
                    </form>

                    <div class="text-sm text-gray-600">
                        Tip: For Excel, use File → Save As → CSV (Comma delimited).
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
