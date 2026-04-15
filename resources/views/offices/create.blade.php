<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Create Office') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <form
                        method="POST"
                        action="{{ route('offices.store') }}"
                        class="space-y-6"
                        x-data="{
                            formationId: @js(old('formation_id', count($formations) > 1 ? '' : optional($formations->first())->id)),
                            selectedDirectorateId: @js(old('directorate_id', '')),
                            name: @js(old('name', '')),
                            type: @js(old('type', '')),
                            parentRef: @js(old('parent_ref', '')),
                            formations: @js($formations->map(fn ($f) => ['id' => $f->id, 'name' => $f->name, 'code' => $f->code])->values()),
                            offices: @js($existingOffices),
                            directorates: @js($directorates->map(fn ($d) => ['id' => $d->id, 'name' => $d->name, 'code' => $d->code])->values()),
                            hasName() {
                                return String(this.name || '').trim().length > 0;
                            },
                            selectedFormation() {
                                const id = String(this.formationId || '');
                                return this.formations.find(f => String(f.id) === id) || null;
                            },
                            isServiceHq() {
                                const f = this.selectedFormation();
                                const code = String(f?.code || '').toUpperCase();
                                const name = String(f?.name || '').toLowerCase();
                                return code === 'SHQ' || name.includes('service headquarters') || name.includes('headquarters') || name.includes('shq');
                            },
                            typeOptions() {
                                return this.isServiceHq() ? ['Division', 'Section', 'Unit'] : ['Section', 'Unit'];
                            },
                            parentRequired() {
                                if (!this.formationId) return false;
                                if (this.isServiceHq()) return this.type === 'Division' || this.type === 'Section' || this.type === 'Unit';
                                return this.type === 'Unit';
                            },
                            parentOptions() {
                                const formationId = String(this.formationId || '');
                                if (!formationId || !this.type) return [];

                                if (!this.isServiceHq()) {
                                    if (this.type !== 'Unit') return [];
                                    return this.offices
                                        .filter(o => String(o.formation_id) === formationId && String(o.type) === 'Section')
                                        .map(o => ({ value: `office:${o.id}`, label: o.name }));
                                }

                                if (this.type === 'Division') {
                                    if (!this.selectedDirectorateId) return [];
                                    return this.directorates
                                        .filter(d => String(d.id) === String(this.selectedDirectorateId))
                                        .map(d => ({ value: `directorate:${d.id}`, label: `${d.name}${d.code ? ` (${d.code})` : ''}` }));
                                }

                                if (this.type === 'Section') {
                                    const dirs = this.directorates
                                        .filter(d => !this.selectedDirectorateId || String(d.id) === String(this.selectedDirectorateId))
                                        .map(d => ({ value: `directorate:${d.id}`, label: `Directorate: ${d.name}${d.code ? ` (${d.code})` : ''}` }));
                                    
                                    const divs = this.offices
                                        .filter(o => {
                                            const matchFormation = String(o.formation_id) === formationId;
                                            const matchType = String(o.type) === 'Division';
                                            const matchDir = !this.selectedDirectorateId || String(o.directorate_id) === String(this.selectedDirectorateId);
                                            return matchFormation && matchType && matchDir;
                                        })
                                        .map(o => ({ value: `office:${o.id}`, label: `Division: ${o.name}` }));
                                    return [...dirs, ...divs];
                                }

                                if (this.type === 'Unit') {
                                    const dirs = this.directorates
                                        .filter(d => !this.selectedDirectorateId || String(d.id) === String(this.selectedDirectorateId))
                                        .map(d => ({ value: `directorate:${d.id}`, label: `Directorate: ${d.name}${d.code ? ` (${d.code})` : ''}` }));
                                    
                                    const divs = this.offices
                                        .filter(o => {
                                            const matchFormation = String(o.formation_id) === formationId;
                                            const matchType = String(o.type) === 'Division';
                                            const matchDir = !this.selectedDirectorateId || String(o.directorate_id) === String(this.selectedDirectorateId);
                                            return matchFormation && matchType && matchDir;
                                        })
                                        .map(o => ({ value: `office:${o.id}`, label: `Division: ${o.name}` }));
                                    
                                    const secs = this.offices
                                        .filter(o => {
                                            const matchFormation = String(o.formation_id) === formationId;
                                            const matchType = String(o.type) === 'Section';
                                            const matchDir = !this.selectedDirectorateId || String(o.directorate_id) === String(this.selectedDirectorateId);
                                            return matchFormation && matchType && matchDir;
                                        })
                                        .map(o => ({ value: `office:${o.id}`, label: `Section: ${o.name}` }));
                                    return [...dirs, ...divs, ...secs];
                                }

                                return [];
                            },
                            onFormationChange() {
                                this.selectedDirectorateId = '';
                                this.type = '';
                                this.parentRef = '';
                            },
                            onDirectorateChange() {
                                this.type = '';
                                this.parentRef = '';
                            },
                            onTypeChange() {
                                const options = this.parentOptions();
                                if (options.length === 1) this.parentRef = String(options[0].value);
                            }
                        }"
                    >
                        @csrf

                        <div>
                            <x-input-label for="name" :value="__('Office Name')" />
                            <x-text-input id="name" name="name" type="text" class="mt-1 block w-full" x-model="name" required autofocus />
                            <x-input-error class="mt-2" :messages="$errors->get('name')" />
                        </div>

                        <div x-show="hasName()" x-cloak class="space-y-6">
                            @if(count($formations) > 1)
                                <div>
                                    <x-input-label for="formation_id" :value="__('Formation')" />
                                    <select id="formation_id" name="formation_id" x-model="formationId" @change="onFormationChange()" class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm" required>
                                        <option value="">Select Formation</option>
                                        @foreach($formations as $formation)
                                            <option value="{{ $formation->id }}" {{ old('formation_id') == $formation->id ? 'selected' : '' }}>
                                                {{ $formation->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    <x-input-error class="mt-2" :messages="$errors->get('formation_id')" />
                                </div>
                            @else
                                <div>
                                    <x-input-label :value="__('Formation')" />
                                    <div class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm px-3 py-2 bg-gray-50 text-gray-800">
                                        {{ $formations->first()?->name }}
                                    </div>
                                    <input type="hidden" name="formation_id" value="{{ $formations->first()?->id }}">
                                </div>
                            @endif

                            <div x-show="formationId && isServiceHq()" x-cloak>
                                <x-input-label for="directorate_id" :value="__('Directorate')" />
                                <select id="directorate_id" name="directorate_id" x-model="selectedDirectorateId" @change="onDirectorateChange()" class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
                                    <option value="">Select Directorate (Optional)</option>
                                    @foreach($directorates as $directorate)
                                        <option value="{{ $directorate->id }}" {{ old('directorate_id') == $directorate->id ? 'selected' : '' }}>
                                            {{ $directorate->name }} {{ $directorate->code ? "({$directorate->code})" : "" }}
                                        </option>
                                    @endforeach
                                </select>
                                <x-input-error class="mt-2" :messages="$errors->get('directorate_id')" />
                            </div>

                            <div x-show="formationId && (!isServiceHq() || selectedDirectorateId)" x-cloak>
                                <x-input-label for="type" :value="__('Type')" />
                                <select id="type" name="type" x-model="type" @change="onTypeChange()" class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm" required>
                                    <option value="">Select Type</option>
                                    <template x-for="opt in typeOptions()" :key="opt">
                                        <option :value="opt" x-text="opt"></option>
                                    </template>
                                </select>
                                <x-input-error class="mt-2" :messages="$errors->get('type')" />
                            </div>

                            <div x-show="formationId && parentRequired()" x-cloak>
                                <x-input-label for="parent_ref" :value="__('Parent')" />
                                <select id="parent_ref" name="parent_ref" x-model="parentRef" class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
                                    <option value="">Select Parent</option>
                                    <template x-for="opt in parentOptions()" :key="opt.value">
                                        <option :value="opt.value" x-text="opt.label"></option>
                                    </template>
                                </select>
                                <x-input-error class="mt-2" :messages="$errors->get('parent_ref')" />
                            </div>
                        </div>

                        <div class="flex justify-end">
                            <x-primary-button>
                                {{ __('Create Office') }}
                            </x-primary-button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
