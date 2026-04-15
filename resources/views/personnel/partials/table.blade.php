<div class="mt-6 overflow-x-auto">
    <table class="min-w-full border-collapse">
        <thead class="bg-gray-50">
            <tr class="text-xs font-semibold text-gray-700">
                <th class="px-4 py-3 text-left whitespace-nowrap">S/N</th>
                <th class="px-4 py-3 text-left whitespace-nowrap">Formation</th>
                <th class="px-4 py-3 text-left whitespace-nowrap">NIS/No</th>
                <th class="px-4 py-3 text-left whitespace-nowrap">Surname</th>
                <th class="px-4 py-3 text-left whitespace-nowrap">Other Names</th>
                <th class="px-4 py-3 text-left whitespace-nowrap">Rank</th>
                <th class="px-4 py-3 text-left whitespace-nowrap">Gender</th>
                <th class="px-4 py-3 text-left whitespace-nowrap">DOB</th>
                <th class="px-4 py-3 text-left whitespace-nowrap">Phone</th>
                <th class="px-4 py-3 text-left whitespace-nowrap">Email</th>
                <th class="px-4 py-3 text-left whitespace-nowrap">Qual</th>
                <th class="px-4 py-3 text-left whitespace-nowrap">Office</th>
                <th class="px-4 py-3 text-left whitespace-nowrap">State</th>
                <th class="px-4 py-3 text-left whitespace-nowrap">LGA</th>
                <th class="px-4 py-3 text-left whitespace-nowrap">DOFA</th>
                <th class="px-4 py-3 text-left whitespace-nowrap">DOPA</th>
                <th class="px-4 py-3 text-left whitespace-nowrap">DOPP</th>
                @if(auth()->user()->isMainAdmin() || auth()->user()->isSuperAdmin())
                    <th class="px-4 py-3 text-left whitespace-nowrap">Action</th>
                @endif
            </tr>
        </thead>
        <tbody class="bg-white divide-y divide-gray-200">
            @forelse($personnel as $index => $person)
                <tr class="text-sm">
                    <td class="px-4 py-4 whitespace-nowrap">{{ $personnel->firstItem() + $index }}</td>
                    <td class="px-4 py-4 whitespace-nowrap text-gray-700">
                        @php
                            $formationName = (string) ($person->formation?->name ?? '');
                            $formationCode = strtoupper((string) ($person->formation?->code ?? ''));
                            $formationLabel = $formationCode === 'SHQ' || str_contains(strtolower($formationName), 'headquarters') ? 'SHQ' : $formationName;
                        @endphp
                        {{ $formationLabel }}
                    </td>
                    <td class="px-4 py-4 whitespace-nowrap">
                        <a href="{{ route('personnel.show', $person) }}" class="text-blue-600 font-semibold hover:underline" @click.prevent="openModal('Personnel Details', '{{ route('personnel.show', $person) }}')">
                            {{ $person->nis_no }}
                        </a>
                    </td>
                    <td class="px-4 py-4 whitespace-nowrap font-semibold text-gray-900">{{ $person->surname }}</td>
                    <td class="px-4 py-4 whitespace-nowrap text-gray-700">{{ $person->first_name }} {{ $person->other_names }}</td>
                    <td class="px-4 py-4 whitespace-nowrap">
                        @if($person->rank_code)
                            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-bold bg-red-100 text-red-700">
                                {{ $person->rank_code }}
                            </span>
                        @endif
                    </td>
                    <td class="px-4 py-4 whitespace-nowrap text-gray-700">{{ $person->gender }}</td>
                    <td class="px-4 py-4 whitespace-nowrap text-gray-700">{{ $person->date_of_birth?->format('d/m/Y') }}</td>
                    <td class="px-4 py-4 whitespace-nowrap text-gray-700">{{ $person->phone }}</td>
                    <td class="px-4 py-4 whitespace-nowrap text-gray-700">{{ $person->email }}</td>
                    <td class="px-4 py-4 whitespace-nowrap text-gray-700">{{ $person->qualification }}</td>
                    <td class="px-4 py-4 whitespace-nowrap text-gray-700">{{ $person->office?->name ?? 'Not assigned' }}</td>
                    <td class="px-4 py-4 whitespace-nowrap text-gray-700">{{ $person->state?->name }}</td>
                    <td class="px-4 py-4 whitespace-nowrap text-gray-700">{{ $person->lga?->name }}</td>
                    <td class="px-4 py-4 whitespace-nowrap text-gray-700">{{ $person->date_of_first_appointment?->format('d/m/Y') }}</td>
                    <td class="px-4 py-4 whitespace-nowrap text-gray-700">{{ $person->date_of_present_appointment?->format('d/m/Y') }}</td>
                    <td class="px-4 py-4 whitespace-nowrap text-gray-700">{{ $person->date_of_present_posting_to_formation?->format('d/m/Y') }}</td>
                    @if(auth()->user()->isMainAdmin() || auth()->user()->isSuperAdmin())
                        <td class="px-4 py-4 whitespace-nowrap space-x-2">
                            @if(auth()->user()->hasAbility('personnel.edit'))
                                <a href="{{ route('personnel.edit', $person->id) }}" class="text-indigo-600 font-semibold hover:underline">
                                    Edit
                                </a>
                            @endif
                            <a href="{{ route('personnel.retire', $person->id) }}" class="text-red-600 font-semibold hover:underline">
                                Retire
                            </a>
                        </td>
                    @endif
                </tr>
            @empty
                <tr>
                    <td colspan="{{ (auth()->user()->isMainAdmin() || auth()->user()->isSuperAdmin()) ? 18 : 17 }}" class="px-6 py-10 text-sm text-gray-500 text-center italic">
                        No personnel found.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

<div class="mt-6">
    {{ $personnel->links() }}
</div>
