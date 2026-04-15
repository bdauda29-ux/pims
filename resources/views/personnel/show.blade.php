@php
    $layout = request()->boolean('modal') ? 'modal-layout' : 'app-layout';
@endphp
<x-dynamic-component :component="$layout">
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Personnel Details') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="{{ request()->boolean('modal') ? 'max-w-4xl' : 'max-w-7xl' }} mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900 space-y-8">
                    <div class="flex items-center gap-4">
                        <div class="h-28 w-28 rounded-md border border-gray-200 bg-gray-50 overflow-hidden flex items-center justify-center">
                            @if($user->photo_path)
                                <img src="{{ asset($user->photo_path) }}" alt="Photograph" class="h-full w-full object-cover" />
                            @else
                                <div class="text-xs text-gray-500">No Photo</div>
                            @endif
                        </div>
                        <div>
                            <div class="text-sm font-semibold text-gray-900">Photograph</div>
                            <div class="text-sm text-gray-600">Passport photo</div>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="space-y-2">
                            <div class="text-xs text-gray-500">NIS No</div>
                            <div class="font-semibold text-gray-900">{{ $user->nis_no }}</div>
                        </div>
                        <div class="space-y-2">
                            <div class="text-xs text-gray-500">Name</div>
                            <div class="font-semibold text-gray-900">{{ $user->surname }} {{ $user->first_name }} {{ $user->other_names }}</div>
                        </div>
                        <div class="space-y-2">
                            <div class="text-xs text-gray-500">Rank</div>
                            <div class="font-semibold text-gray-900">{{ $user->rank_code }}{{ $user->rank ? " ({$user->rank})" : '' }}</div>
                        </div>
                        <div class="space-y-2">
                            <div class="text-xs text-gray-500">Gender</div>
                            <div class="font-semibold text-gray-900">{{ $user->gender }}</div>
                        </div>
                        <div class="space-y-2">
                            <div class="text-xs text-gray-500">Date of Birth</div>
                            <div class="font-semibold text-gray-900">{{ $user->date_of_birth?->format('d/m/Y') }}</div>
                        </div>
                        <div class="space-y-2">
                            <div class="text-xs text-gray-500">Phone</div>
                            <div class="font-semibold text-gray-900">{{ $user->phone }}</div>
                        </div>
                        <div class="space-y-2">
                            <div class="text-xs text-gray-500">Email</div>
                            <div class="font-semibold text-gray-900">{{ $user->email }}</div>
                        </div>
                        <div class="space-y-2">
                            <div class="text-xs text-gray-500">Qualification</div>
                            <div class="font-semibold text-gray-900">{{ $user->qualification }}</div>
                        </div>
                        <div class="space-y-2">
                            <div class="text-xs text-gray-500">Field of Study</div>
                            <div class="font-semibold text-gray-900">{{ $user->field_of_study }}</div>
                        </div>
                        <div class="space-y-2">
                            <div class="text-xs text-gray-500">State</div>
                            <div class="font-semibold text-gray-900">{{ $user->state?->name }}</div>
                        </div>
                        <div class="space-y-2">
                            <div class="text-xs text-gray-500">LGA</div>
                            <div class="font-semibold text-gray-900">{{ $user->lga?->name }}</div>
                        </div>
                    </div>

                    <div class="border-t border-gray-200 pt-6">
                        <div class="text-sm font-semibold text-gray-900">Posting</div>
                        <div class="mt-4 grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div class="space-y-2">
                                <div class="text-xs text-gray-500">Formation</div>
                                <div class="font-semibold text-gray-900">{{ $user->formation?->code === 'SHQ' ? 'SHQ' : $user->formation?->name }}</div>
                            </div>
                            <div class="space-y-2">
                                <div class="text-xs text-gray-500">Office</div>
                                <div class="font-semibold text-gray-900">{{ $user->office?->name ?? 'Not assigned' }}</div>
                            </div>
                            <div class="space-y-2">
                                <div class="text-xs text-gray-500">Directorate</div>
                                <div class="font-semibold text-gray-900">{{ $user->directorate?->name }}</div>
                            </div>
                            <div class="space-y-2">
                                <div class="text-xs text-gray-500">Date of First Appointment</div>
                                <div class="font-semibold text-gray-900">{{ $user->date_of_first_appointment?->format('d/m/Y') }}</div>
                            </div>
                            <div class="space-y-2">
                                <div class="text-xs text-gray-500">Date of Present Appointment</div>
                                <div class="font-semibold text-gray-900">{{ $user->date_of_present_appointment?->format('d/m/Y') }}</div>
                            </div>
                            <div class="space-y-2">
                                <div class="text-xs text-gray-500">Date of Posting to Formation</div>
                                <div class="font-semibold text-gray-900">{{ $user->date_of_present_posting_to_formation?->format('d/m/Y') }}</div>
                            </div>
                        </div>
                    </div>

                    <div class="border-t border-gray-200 pt-6">
                        <div class="text-sm font-semibold text-gray-900">Next of Kin</div>
                        <div class="mt-4 grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div class="space-y-2">
                                <div class="text-xs text-gray-500">NOK Name</div>
                                <div class="font-semibold text-gray-900">{{ $user->nok_name }}</div>
                            </div>
                            <div class="space-y-2">
                                <div class="text-xs text-gray-500">NOK Phone</div>
                                <div class="font-semibold text-gray-900">{{ $user->nok_phone }}</div>
                            </div>
                        </div>
                    </div>

                    <div class="border-t border-gray-200 pt-6">
                        <div class="text-sm font-semibold text-gray-900">Finance</div>
                        <div class="mt-4 grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div class="space-y-2">
                                <div class="text-xs text-gray-500">Salary Account Number</div>
                                <div class="font-semibold text-gray-900">{{ $user->salary_account_number }}</div>
                            </div>
                            <div class="space-y-2">
                                <div class="text-xs text-gray-500">Bank</div>
                                <div class="font-semibold text-gray-900">{{ $user->bank }}</div>
                            </div>
                            <div class="space-y-2">
                                <div class="text-xs text-gray-500">PFA Number</div>
                                <div class="font-semibold text-gray-900">{{ $user->pfa_number }}</div>
                            </div>
                            <div class="space-y-2">
                                <div class="text-xs text-gray-500">PFA Name</div>
                                <div class="font-semibold text-gray-900">{{ $user->pfa_name }}</div>
                            </div>
                            <div class="space-y-2">
                                <div class="text-xs text-gray-500">NHF No</div>
                                <div class="font-semibold text-gray-900">{{ $user->nhf_no }}</div>
                            </div>
                            <div class="space-y-2">
                                <div class="text-xs text-gray-500">IPPIS No</div>
                                <div class="font-semibold text-gray-900">{{ $user->ippis_no }}</div>
                            </div>
                        </div>
                    </div>

                    <div class="border-t border-gray-200 pt-6">
                        <div class="text-sm font-semibold text-gray-900">Custom Fields</div>
                        <div class="mt-4 overflow-x-auto">
                            <table class="min-w-full border-collapse">
                                <thead class="bg-gray-50">
                                    <tr class="text-xs font-semibold text-gray-700">
                                        <th class="px-4 py-3 text-left">Field</th>
                                        <th class="px-4 py-3 text-left">Value</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-200">
                                    @forelse($customFields as $field)
                                        @php
                                            $value = $customValues[$field->id] ?? null;
                                        @endphp
                                        <tr class="text-sm">
                                            <td class="px-4 py-3 font-semibold text-gray-900">{{ $field->label }}</td>
                                            <td class="px-4 py-3 text-gray-700">{{ $value }}</td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="2" class="px-4 py-6 text-sm text-gray-500 text-center italic">No custom fields.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="border-t border-gray-200 pt-6">
                        <div class="text-sm font-semibold text-gray-900">Remark</div>
                        <div class="mt-2 text-sm text-gray-700 whitespace-pre-line">{{ $user->remark }}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-dynamic-component>
