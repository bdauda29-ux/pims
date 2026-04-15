{{-- 
    This view displays the Personnel Registration form.
    It uses dynamic JavaScript to load LGAs based on the selected State.
--}}
@php
    $layout = request()->boolean('modal') ? 'modal-layout' : 'app-layout';
    $lockFilledFields = (bool) ($lockFilledFields ?? false);
    $canEditRemark = (bool) ($canEditRemark ?? false);
@endphp
<x-dynamic-component :component="$layout">
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Edit Personnel') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    {{-- FORM START --}}
                    <form method="POST" action="{{ route('personnel.update', $user) }}" class="space-y-6">
                        @csrf
                        @method('PATCH')

                        {{-- Form Grid: Split into two columns on larger screens --}}
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            
                            {{-- 1. NIS Number --}}
                            <div>
                                <x-input-label for="nis_no" :value="__('NIS No')" />
                                <x-text-input id="nis_no" name="nis_no" type="text" class="mt-1 block w-full" :value="old('nis_no', $user->nis_no)" required autofocus readonly />
                                <x-input-error class="mt-2" :messages="$errors->get('nis_no')" />
                            </div>

                            {{-- 2. Email Address --}}
                            <div>
                                <x-input-label for="email" :value="__('Email Address')" />
                                <x-text-input id="email" name="email" type="email" class="mt-1 block w-full" :value="old('email', $user->email)" required readonly />
                                <x-input-error class="mt-2" :messages="$errors->get('email')" />
                            </div>

                            <div>
                                <x-input-label for="phone" :value="__('Phone Number')" />
                                <x-text-input id="phone" name="phone" type="text" class="mt-1 block w-full" :value="old('phone', $user->phone)" :readonly="$lockFilledFields && trim((string) $user->phone) !== ''" />
                                <x-input-error class="mt-2" :messages="$errors->get('phone')" />
                            </div>

                            {{-- 3. Surname --}}
                            <div>
                                <x-input-label for="surname" :value="__('Surname')" />
                                <x-text-input id="surname" name="surname" type="text" class="mt-1 block w-full" :value="old('surname', $user->surname)" required :readonly="$lockFilledFields && trim((string) $user->surname) !== ''" />
                                <x-input-error class="mt-2" :messages="$errors->get('surname')" />
                            </div>

                            {{-- 4. First Name --}}
                            <div>
                                <x-input-label for="first_name" :value="__('First Name')" />
                                <x-text-input id="first_name" name="first_name" type="text" class="mt-1 block w-full" :value="old('first_name', $user->first_name)" required :readonly="$lockFilledFields && trim((string) $user->first_name) !== ''" />
                                <x-input-error class="mt-2" :messages="$errors->get('first_name')" />
                            </div>

                            {{-- 5. Other Names --}}
                            <div>
                                <x-input-label for="other_names" :value="__('Other Names')" />
                                <x-text-input id="other_names" name="other_names" type="text" class="mt-1 block w-full" :value="old('other_names', $user->other_names)" :readonly="$lockFilledFields && trim((string) $user->other_names) !== ''" />
                                <x-input-error class="mt-2" :messages="$errors->get('other_names')" />
                            </div>

                            {{-- 6. Gender Selection --}}
                            <div>
                                <x-input-label for="gender" :value="__('Gender')" />
                                <select id="gender" name="gender" class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm" required {{ $lockFilledFields && trim((string) $user->gender) !== '' ? 'disabled' : '' }}>
                                    <option value="">Select Gender</option>
                                    <option value="Male" {{ old('gender', $user->gender) == 'Male' ? 'selected' : '' }}>Male</option>
                                    <option value="Female" {{ old('gender', $user->gender) == 'Female' ? 'selected' : '' }}>Female</option>
                                </select>
                                @if($lockFilledFields && trim((string) $user->gender) !== '')
                                    <input type="hidden" name="gender" value="{{ old('gender', $user->gender) }}">
                                @endif
                                <x-input-error class="mt-2" :messages="$errors->get('gender')" />
                            </div>

                            {{-- 7. Rank --}}
                            <div>
                                <x-input-label for="rank_code" :value="__('Rank')" />
                                <select id="rank_code" name="rank_code" class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm" required {{ $lockFilledFields && trim((string) $user->rank_code) !== '' ? 'disabled' : '' }}>
                                    <option value="">Select Rank</option>
                                    @foreach($ranks as $rank)
                                        <option value="{{ $rank['code'] }}" {{ old('rank_code', $user->rank_code) == $rank['code'] ? 'selected' : '' }}>
                                            {{ $rank['name'] }} ({{ $rank['code'] }})
                                        </option>
                                    @endforeach
                                </select>
                                @if($lockFilledFields && trim((string) $user->rank_code) !== '')
                                    <input type="hidden" name="rank_code" value="{{ old('rank_code', $user->rank_code) }}">
                                @endif
                                <x-input-error class="mt-2" :messages="$errors->get('rank_code')" />
                            </div>

                            {{-- 8. Date of Birth --}}
                            <div>
                                <x-input-label for="date_of_birth" :value="__('Date of Birth')" />
                                <x-text-input id="date_of_birth" name="date_of_birth" type="date" class="mt-1 block w-full" :value="old('date_of_birth', $user->date_of_birth?->format('Y-m-d'))" required :readonly="$lockFilledFields && (bool) $user->date_of_birth" />
                                <x-input-error class="mt-2" :messages="$errors->get('date_of_birth')" />
                            </div>

                            {{-- 9. State of Origin: Triggers JavaScript 'fetchLgas' on change --}}
                            <div>
                                <x-input-label for="state_id" :value="__('State of Origin')" />
                                <select id="state_id" name="state_id" class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm" required onchange="fetchLgas(this.value)" {{ $lockFilledFields && $user->state_id ? 'disabled' : '' }}>
                                    <option value="">Select State</option>
                                    @foreach($states as $state)
                                        <option value="{{ $state->id }}" {{ old('state_id', $user->state_id) == $state->id ? 'selected' : '' }}>{{ $state->name }}</option>
                                    @endforeach
                                </select>
                                @if($lockFilledFields && $user->state_id)
                                    <input type="hidden" name="state_id" value="{{ old('state_id', $user->state_id) }}">
                                @endif
                                <x-input-error class="mt-2" :messages="$errors->get('state_id')" />
                            </div>

                            {{-- 10. Local Government: Populated dynamically via JavaScript --}}
                            <div>
                                <x-input-label for="lga_id" :value="__('Local Government')" />
                                <select id="lga_id" name="lga_id" class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm" required {{ $lockFilledFields && $user->lga_id ? 'disabled' : '' }}>
                                    <option value="">Select LGA</option>
                                </select>
                                @if($lockFilledFields && $user->lga_id)
                                    <input type="hidden" name="lga_id" value="{{ old('lga_id', $user->lga_id) }}">
                                @endif
                                <x-input-error class="mt-2" :messages="$errors->get('lga_id')" />
                            </div>

                            {{-- 11. Formation: Conditional display based on admin role --}}
                            <div>
                                <x-input-label for="formation_id" :value="__('Formation')" />
                                @php
                                    $presetFormation = null;
                                    if(isset($presetFormationId) && $presetFormationId) {
                                        $presetFormation = $formations->firstWhere('id', (int) $presetFormationId);
                                    }
                                @endphp
                                @if(count($formations) === 1)
                                    {{-- If only one formation (e.g. Formation Admin), show it as read-only text --}}
                                    @php
                                        $onlyFormation = $formations->first();
                                        $onlyCode = strtoupper((string) ($onlyFormation->code ?? ''));
                                        $onlyName = strtolower((string) ($onlyFormation->name ?? ''));
                                        $onlyLabel = ($onlyCode === 'SHQ' || str_contains($onlyName, 'headquarters') || str_contains($onlyName, 'shq')) ? 'SHQ' : (string) $onlyFormation->name;
                                    @endphp
                                    <x-text-input type="text" class="mt-1 block w-full bg-gray-50 text-gray-500 cursor-not-allowed" value="{{ $onlyLabel }}" readonly />
                                    <input type="hidden" name="formation_id" value="{{ $onlyFormation->id }}" data-name="{{ $onlyFormation->name }}" data-code="{{ $onlyFormation->code }}">
                                @elseif($presetFormation)
                                    @php
                                        $pCode = strtoupper((string) ($presetFormation->code ?? ''));
                                        $pName = strtolower((string) ($presetFormation->name ?? ''));
                                        $pLabel = ($pCode === 'SHQ' || str_contains($pName, 'headquarters') || str_contains($pName, 'shq')) ? 'SHQ' : (string) $presetFormation->name;
                                    @endphp
                                    <x-text-input type="text" class="mt-1 block w-full bg-gray-50 text-gray-500 cursor-not-allowed" value="{{ $pLabel }}{{ $presetFormation->code ? ' ('.$presetFormation->code.')' : '' }}" readonly />
                                    <input type="hidden" name="formation_id" value="{{ $presetFormation->id }}" data-name="{{ $presetFormation->name }}" data-code="{{ $presetFormation->code }}">
                                @else
                                    {{-- If multiple formations (Main Admin), show a dropdown --}}
                                    <select id="formation_id" name="formation_id" class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm" required onchange="fetchOffices(this.value); toggleDirectorate();">
                                        <option value="">Select Formation</option>
                                        @foreach($formations as $formation)
                                            @php
                                                $fCode = strtoupper((string) ($formation->code ?? ''));
                                                $fName = strtolower((string) ($formation->name ?? ''));
                                                $fLabel = ($fCode === 'SHQ' || str_contains($fName, 'headquarters') || str_contains($fName, 'shq')) ? 'SHQ' : (string) $formation->name;
                                            @endphp
                                            <option value="{{ $formation->id }}" data-name="{{ $formation->name }}" data-code="{{ $formation->code }}" {{ old('formation_id', $user->formation_id) == $formation->id ? 'selected' : '' }}>{{ $fLabel }}{{ $formation->code ? ' ('.$formation->code.')' : '' }}</option>
                                        @endforeach
                                    </select>
                                @endif
                                <x-input-error class="mt-2" :messages="$errors->get('formation_id')" />
                            </div>

                            <div id="directorate_wrapper" class="hidden">
                                <x-input-label for="directorate_id" :value="__('Directorate')" />
                                <select id="directorate_id" name="directorate_id" class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm" {{ $lockFilledFields && $user->directorate_id ? 'disabled' : '' }}>
                                    <option value="">Not assigned</option>
                                    @foreach($directorates as $directorate)
                                        <option value="{{ $directorate->id }}" {{ old('directorate_id', $user->directorate_id) == $directorate->id ? 'selected' : '' }}>
                                            {{ $directorate->name }}
                                        </option>
                                    @endforeach
                                </select>
                                @if($lockFilledFields && $user->directorate_id)
                                    <input type="hidden" name="directorate_id" value="{{ old('directorate_id', $user->directorate_id) }}">
                                @endif
                                <x-input-error class="mt-2" :messages="$errors->get('directorate_id')" />
                            </div>

                            @if(!empty($showOfficeField))
                                <div>
                                    <x-input-label for="office_id" :value="__('Office')" />
                                    <select id="office_id" name="office_id" class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm" {{ (($lockFilledFields && $user->office_id) || empty($canMoveOffice)) ? 'disabled' : '' }}>
                                        <option value="">Not assigned</option>
                                        @foreach($offices as $office)
                                            <option value="{{ $office->id }}" {{ old('office_id', $user->office_id) == $office->id ? 'selected' : '' }}>{{ $office->name }}</option>
                                        @endforeach
                                    </select>
                                    @if((($lockFilledFields && $user->office_id) || empty($canMoveOffice)))
                                        <input type="hidden" name="office_id" value="{{ old('office_id', $user->office_id) }}">
                                    @endif
                                    <x-input-error class="mt-2" :messages="$errors->get('office_id')" />
                                </div>
                            @endif

                            {{-- 12. Date of First Appointment --}}
                            <div>
                                <x-input-label for="date_of_first_appointment" :value="__('Date of First Appointment')" />
                                <x-text-input id="date_of_first_appointment" name="date_of_first_appointment" type="date" class="mt-1 block w-full" :value="old('date_of_first_appointment', $user->date_of_first_appointment?->format('Y-m-d'))" required :readonly="$lockFilledFields && (bool) $user->date_of_first_appointment" />
                                <x-input-error class="mt-2" :messages="$errors->get('date_of_first_appointment')" />
                            </div>

                            {{-- 13. Date of Present Appointment --}}
                            <div>
                                <x-input-label for="date_of_present_appointment" :value="__('Date of Present Appointment')" />
                                <x-text-input id="date_of_present_appointment" name="date_of_present_appointment" type="date" class="mt-1 block w-full" :value="old('date_of_present_appointment', $user->date_of_present_appointment?->format('Y-m-d'))" required :readonly="$lockFilledFields && (bool) $user->date_of_present_appointment" />
                                <x-input-error class="mt-2" :messages="$errors->get('date_of_present_appointment')" />
                            </div>

                            <div>
                                <x-input-label for="date_of_present_posting_to_formation" :value="__('Date of Present Posting to Formation (DOPP)')" />
                                <x-text-input id="date_of_present_posting_to_formation" name="date_of_present_posting_to_formation" type="date" class="mt-1 block w-full" :value="old('date_of_present_posting_to_formation', $user->date_of_present_posting_to_formation?->format('Y-m-d'))" :readonly="$lockFilledFields && (bool) $user->date_of_present_posting_to_formation" />
                                <x-input-error class="mt-2" :messages="$errors->get('date_of_present_posting_to_formation')" />
                            </div>

                            <div>
                                <x-input-label for="qualification" :value="__('Qualification')" />
                                <select id="qualification" name="qualification" class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm" {{ $lockFilledFields && trim((string) $user->qualification) !== '' ? 'disabled' : '' }}>
                                    <option value="">Select Qualification</option>
                                    @foreach($qualifications as $q)
                                        <option value="{{ $q }}" {{ old('qualification', $user->qualification) == $q ? 'selected' : '' }}>{{ $q }}</option>
                                    @endforeach
                                </select>
                                @if($lockFilledFields && trim((string) $user->qualification) !== '')
                                    <input type="hidden" name="qualification" value="{{ old('qualification', $user->qualification) }}">
                                @endif
                                <x-input-error class="mt-2" :messages="$errors->get('qualification')" />
                            </div>

                            <div>
                                <x-input-label for="field_of_study" :value="__('Field of Study')" />
                                <x-text-input id="field_of_study" name="field_of_study" type="text" class="mt-1 block w-full" :value="old('field_of_study', $user->field_of_study)" :readonly="$lockFilledFields && trim((string) $user->field_of_study) !== ''" />
                                <x-input-error class="mt-2" :messages="$errors->get('field_of_study')" />
                            </div>

                            <div>
                                <x-input-label for="salary_account_number" :value="__('Salary Account Number')" />
                                <x-text-input id="salary_account_number" name="salary_account_number" type="text" class="mt-1 block w-full" :value="old('salary_account_number', $user->salary_account_number)" :readonly="$lockFilledFields && trim((string) $user->salary_account_number) !== ''" />
                                <x-input-error class="mt-2" :messages="$errors->get('salary_account_number')" />
                            </div>

                            <div>
                                <x-input-label for="bank" :value="__('Bank')" />
                                <select id="bank" name="bank" class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm" {{ $lockFilledFields && trim((string) $user->bank) !== '' ? 'disabled' : '' }}>
                                    <option value="">Select Bank</option>
                                    @foreach($banks as $bank)
                                        <option value="{{ $bank }}" {{ old('bank', $user->bank) == $bank ? 'selected' : '' }}>{{ $bank }}</option>
                                    @endforeach
                                </select>
                                @if($lockFilledFields && trim((string) $user->bank) !== '')
                                    <input type="hidden" name="bank" value="{{ old('bank', $user->bank) }}">
                                @endif
                                <x-input-error class="mt-2" :messages="$errors->get('bank')" />
                            </div>

                            <div>
                                <x-input-label for="pfa_number" :value="__('PFA Number')" />
                                <x-text-input id="pfa_number" name="pfa_number" type="text" class="mt-1 block w-full" :value="old('pfa_number', $user->pfa_number)" :readonly="$lockFilledFields && trim((string) $user->pfa_number) !== ''" />
                                <x-input-error class="mt-2" :messages="$errors->get('pfa_number')" />
                            </div>

                            <div>
                                <x-input-label for="pfa_name" :value="__('PFA Name')" />
                                <x-text-input id="pfa_name" name="pfa_name" type="text" class="mt-1 block w-full" :value="old('pfa_name', $user->pfa_name)" :readonly="$lockFilledFields && trim((string) $user->pfa_name) !== ''" />
                                <x-input-error class="mt-2" :messages="$errors->get('pfa_name')" />
                            </div>

                            <div>
                                <x-input-label for="nhf_no" :value="__('NHF No')" />
                                <x-text-input id="nhf_no" name="nhf_no" type="text" class="mt-1 block w-full" :value="old('nhf_no', $user->nhf_no)" :readonly="$lockFilledFields && trim((string) $user->nhf_no) !== ''" />
                                <x-input-error class="mt-2" :messages="$errors->get('nhf_no')" />
                            </div>

                            <div>
                                <x-input-label for="ippis_no" :value="__('IPPIS No')" />
                                <x-text-input id="ippis_no" name="ippis_no" type="text" class="mt-1 block w-full" :value="old('ippis_no', $user->ippis_no)" :readonly="$lockFilledFields && trim((string) $user->ippis_no) !== ''" />
                                <x-input-error class="mt-2" :messages="$errors->get('ippis_no')" />
                            </div>

                            <div>
                                <x-input-label for="nok_name" :value="__('Next of Kin (NOK)')" />
                                <x-text-input id="nok_name" name="nok_name" type="text" class="mt-1 block w-full" :value="old('nok_name', $user->nok_name)" :readonly="$lockFilledFields && trim((string) $user->nok_name) !== ''" />
                                <x-input-error class="mt-2" :messages="$errors->get('nok_name')" />
                            </div>

                            <div>
                                <x-input-label for="nok_phone" :value="__('NOK Phone Number')" />
                                <x-text-input id="nok_phone" name="nok_phone" type="text" class="mt-1 block w-full" :value="old('nok_phone', $user->nok_phone)" :readonly="$lockFilledFields && trim((string) $user->nok_phone) !== ''" />
                                <x-input-error class="mt-2" :messages="$errors->get('nok_phone')" />
                            </div>

                            <div class="md:col-span-2">
                                <x-input-label for="remark" :value="__('Remark')" />
                                <textarea id="remark" name="remark" class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm" rows="3" {{ ! $canEditRemark || ($lockFilledFields && trim((string) $user->remark) !== '') ? 'readonly' : '' }}>{{ old('remark', $user->remark) }}</textarea>
                                <x-input-error class="mt-2" :messages="$errors->get('remark')" />
                            </div>

                            {{-- 14. System Role --}}
                            <div>
                                <x-input-label for="role" :value="__('Role')" />
                                <select id="role" name="role" class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm" required {{ $lockFilledFields ? 'disabled' : '' }}>
                                    <option value="User" {{ old('role', $user->role) == 'User' ? 'selected' : '' }}>User</option>
                                    @if(auth()->user()->isMainAdmin())
                                        <option value="Office Admin" {{ old('role', $user->role) == 'Office Admin' ? 'selected' : '' }}>Office Admin</option>
                                    @endif
                                </select>
                                @if($lockFilledFields)
                                    <input type="hidden" name="role" value="{{ old('role', $user->role) }}">
                                @endif
                                <x-input-error class="mt-2" :messages="$errors->get('role')" />
                            </div>

                            @foreach($customFields as $field)
                                @php
                                    $inputName = "custom[{$field->key}]";
                                    $oldValue = old("custom.{$field->key}", $customValues[$field->id] ?? null);
                                @endphp
                                <div class="{{ $field->type === 'textarea' ? 'md:col-span-2' : '' }}">
                                    <x-input-label :for="'cf_'.$field->key" :value="$field->label.($field->required ? ' *' : '')" />
                                    @if($field->type === 'textarea')
                                        <textarea id="{{ 'cf_'.$field->key }}" name="{{ $inputName }}" rows="3" class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm" {{ $lockFilledFields && trim((string) ($customValues[$field->id] ?? '')) !== '' ? 'readonly' : '' }}>{{ $oldValue }}</textarea>
                                    @elseif($field->type === 'number')
                                        <x-text-input :id="'cf_'.$field->key" :name="$inputName" type="number" class="mt-1 block w-full" :value="$oldValue" :readonly="$lockFilledFields && trim((string) ($customValues[$field->id] ?? '')) !== ''" />
                                    @elseif($field->type === 'date')
                                        <x-text-input :id="'cf_'.$field->key" :name="$inputName" type="date" class="mt-1 block w-full" :value="$oldValue" :readonly="$lockFilledFields && trim((string) ($customValues[$field->id] ?? '')) !== ''" />
                                    @elseif($field->type === 'select')
                                        <select id="{{ 'cf_'.$field->key }}" name="{{ $inputName }}" class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm" {{ $lockFilledFields && trim((string) ($customValues[$field->id] ?? '')) !== '' ? 'disabled' : '' }}>
                                            <option value="">Select</option>
                                            @foreach(($field->options ?? []) as $opt)
                                                <option value="{{ $opt }}" {{ (string) $oldValue === (string) $opt ? 'selected' : '' }}>{{ $opt }}</option>
                                            @endforeach
                                        </select>
                                        @if($lockFilledFields && trim((string) ($customValues[$field->id] ?? '')) !== '')
                                            <input type="hidden" name="{{ $inputName }}" value="{{ $oldValue }}">
                                        @endif
                                    @else
                                        <x-text-input :id="'cf_'.$field->key" :name="$inputName" type="text" class="mt-1 block w-full" :value="$oldValue" :readonly="$lockFilledFields && trim((string) ($customValues[$field->id] ?? '')) !== ''" />
                                    @endif
                                    <x-input-error class="mt-2" :messages="$errors->get('custom.'.$field->key)" />
                                </div>
                            @endforeach
                        </div>

                        {{-- SUBMIT BUTTON --}}
                        <div class="flex items-center justify-end mt-4">
                            <x-primary-button class="ms-4">
                                {{ __('Edit Personnel') }}
                            </x-primary-button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    {{-- DYNAMIC JAVASCRIPT --}}
    <script>
        const currentRole = @json(auth()->user()->role);
        const currentDirectorateId = @json(auth()->user()->directorate_id);

        /**
         * fetchLgas
         * Fetches LGAs for a selected State via our API route.
         */
        function fetchLgas(stateId) {
            const lgaSelect = document.getElementById('lga_id');
            lgaSelect.innerHTML = '<option value="">Loading...</option>';

            if (!stateId) {
                lgaSelect.innerHTML = '<option value="">Select LGA</option>';
                return;
            }

            // Standard JavaScript Fetch API
            fetch(`/api/states/${stateId}/lgas`)
                .then(response => response.json())
                .then(data => {
                    lgaSelect.innerHTML = '<option value="">Select LGA</option>';
                    data.forEach(lga => {
                        const option = document.createElement('option');
                        option.value = lga.id;
                        option.textContent = lga.name;
                        lgaSelect.appendChild(option);
                    });
                })
                .catch(error => {
                    console.error('Error fetching LGAs:', error);
                    lgaSelect.innerHTML = '<option value="">Error loading LGAs</option>';
                });
        }

        function fetchOffices(formationId, selectedOfficeId = null) {
            const officeSelect = document.getElementById('office_id');
            if (!officeSelect) return;

            officeSelect.innerHTML = '<option value="">Not assigned</option>';

            if (!formationId) {
                return;
            }

            fetch(`/api/formations/${formationId}/offices`)
                .then(response => response.json())
                .then(data => {
                    officeSelect.innerHTML = '<option value="">Not assigned</option>';
                    data.forEach(office => {
                        if (currentRole === 'DCG' && currentDirectorateId && String(office.directorate_id) !== String(currentDirectorateId)) {
                            return;
                        }
                        const option = document.createElement('option');
                        option.value = office.id;
                        option.textContent = office.name;
                        if (selectedOfficeId && String(office.id) === String(selectedOfficeId)) {
                            option.selected = true;
                        }
                        officeSelect.appendChild(option);
                    });
                });
        }

        function isServiceHqSelected() {
            const formationSelect = document.getElementById('formation_id');
            if (formationSelect) {
                const opt = formationSelect.options[formationSelect.selectedIndex];
                const name = (opt?.dataset?.name || '').toLowerCase();
                const code = (opt?.dataset?.code || '').toUpperCase();
                return code === 'SHQ' || name.includes('service headquarters') || name.includes('headquarters') || name.includes('shq');
            }

            const formationHidden = document.querySelector('input[name="formation_id"]');
            const name = (formationHidden?.dataset?.name || '').toLowerCase();
            const code = (formationHidden?.dataset?.code || '').toUpperCase();
            return code === 'SHQ' || name.includes('service headquarters') || name.includes('headquarters') || name.includes('shq');
        }

        function toggleDirectorate() {
            const wrapper = document.getElementById('directorate_wrapper');
            const directorateSelect = document.getElementById('directorate_id');
            if (!wrapper || !directorateSelect) return;

            if (isServiceHqSelected()) {
                wrapper.classList.remove('hidden');
            } else {
                directorateSelect.value = '';
                wrapper.classList.add('hidden');
            }
        }

        window.addEventListener('load', function () {
            const formationSelect = document.getElementById('formation_id');
            const formationHidden = document.querySelector('input[name="formation_id"]');
            const formationId = formationSelect ? formationSelect.value : (formationHidden ? formationHidden.value : '');
            const oldOfficeId = "{{ old('office_id', $user->office_id) }}";
            if (formationId) {
                fetchOffices(formationId, oldOfficeId || null);
            }
            toggleDirectorate();
        });

        const oldStateId = @json(old('state_id', $user->state_id));
        const oldLgaId = @json(old('lga_id', $user->lga_id));
        window.addEventListener('load', function () {
            if (!oldStateId) return;
            fetch(`/api/states/${oldStateId}/lgas`)
                .then(response => response.json())
                .then(data => {
                    const lgaSelect = document.getElementById('lga_id');
                    lgaSelect.innerHTML = '<option value="">Select LGA</option>';
                    data.forEach(lga => {
                        const option = document.createElement('option');
                        option.value = lga.id;
                        option.textContent = lga.name;
                        if (String(lga.id) === String(oldLgaId)) {
                            option.selected = true;
                        }
                        lgaSelect.appendChild(option);
                    });
                });
        });

    </script>
</x-dynamic-component>
