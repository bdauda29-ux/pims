<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Personnel Management') }}
        </h2>
    </x-slot>

    <div
        class="py-12"
        x-data="{
            tab: @js(request('tab') === 'custom' ? 'custom' : 'list'),
            status: @js(in_array(request('status'), ['completed','incomplete'], true) ? request('status') : (in_array(request('tab'), ['completed','incomplete'], true) ? request('tab') : 'completed')),
            exportMenuOpen: false,
            exportOpen: false,
            modalOpen: false,
            modalTitle: '',
            modalUrl: '',
            filtersOpen: false,
            loading: false,
            q: @js(request('q', '')),
            gender: @js(request('gender', '')),
            rankCode: @js(request('rank_code', '')),
            officeId: @js(request('office_id', '')),
            formationId: @js(request('formation_id', '')),
            directorateId: @js(request('directorate_id', '')),
            dopa: @js(request('dopa', '')),
            dopp: @js(request('dopp', '')),
            formations: @js($formations->map(fn ($f) => ['id' => $f->id, 'name' => $f->name, 'code' => $f->code])->values()),
            directorates: @js($directorates->map(fn ($d) => ['id' => $d->id, 'name' => $d->name, 'code' => $d->code])->values()),
            ranks: @js($ranks ?? []),
            offices: [],
            currentRole: @js(auth()->user()->role),
            currentDirectorateId: @js(auth()->user()->directorate_id),
            currentOfficeId: @js(auth()->user()->office_id),
            format: 'xls',
            mergeName: false,
            fields: @js($defaultExportFields),
            exportFieldLabels: @js($exportFieldLabels),
            draggingIndex: null,
            dragOverIndex: null,
            openModal(title, url) {
                this.modalTitle = title;
                const hasQuery = String(url || '').includes('?');
                this.modalUrl = url + (hasQuery ? '&' : '?') + 'modal=1';
                this.modalOpen = true;
            },
            closeModal() {
                this.modalOpen = false;
                this.modalTitle = '';
                this.modalUrl = '';
            },
            hasActiveFilters() {
                return Boolean(String(this.q || '').trim())
                    || Boolean(this.gender)
                    || Boolean(this.rankCode)
                    || Boolean(this.officeId)
                    || Boolean(this.formationId)
                    || Boolean(this.directorateId)
                    || Boolean(this.dopa)
                    || Boolean(this.dopp);
            },
            isShqSelected() {
                const formationId = String(this.formationId || '');
                if (!formationId) return false;
                const f = this.formations.find(x => String(x.id) === formationId);
                const code = String(f?.code || '').toUpperCase();
                const name = String(f?.name || '').toLowerCase();
                return code === 'SHQ' || name.includes('service headquarters') || name.includes('headquarters') || name.includes('shq');
            },
            buildParams(extra = {}) {
                const params = new URLSearchParams();
                params.set('tab', this.tab);
                params.set('status', this.status);
                if (String(this.q || '').trim()) params.set('q', String(this.q || '').trim());
                if (this.gender) params.set('gender', this.gender);
                if (this.rankCode) params.set('rank_code', this.rankCode);
                if (this.officeId) params.set('office_id', this.officeId);
                if (this.formationId) params.set('formation_id', this.formationId);
                if (this.directorateId) params.set('directorate_id', this.directorateId);
                if (this.dopa) params.set('dopa', this.dopa);
                if (this.dopp) params.set('dopp', this.dopp);
                Object.entries(extra).forEach(([k, v]) => {
                    if (v === null || v === undefined || v === '') return;
                    params.set(k, v);
                });
                return params;
            },
            updateUrl(params) {
                const url = `${window.location.pathname}?${params.toString()}`;
                window.history.replaceState({}, '', url);
            },
            fetchList() {
                this.loading = true;
                const params = this.buildParams({ ajax: 1 });
                this.updateUrl(this.buildParams());
                fetch(`{{ route('personnel.index') }}?${params.toString()}`, {
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                })
                    .then(r => r.json())
                    .then(({ html }) => {
                        const el = document.getElementById('personnelTable');
                        if (el) {
                            el.innerHTML = html || '';
                            if (window.Alpine && typeof window.Alpine.initTree === 'function') {
                                window.Alpine.initTree(el);
                            }
                        }
                    })
                    .finally(() => { this.loading = false; });
            },
            onSearchInput() {
                this.fetchList();
            },
            applyFilters() {
                this.fetchList();
            },
            resetFilters() {
                this.q = '';
                this.gender = '';
                this.rankCode = '';
                this.officeId = '';
                this.formationId = '';
                this.directorateId = '';
                this.dopa = '';
                this.dopp = '';
                this.fetchList();
            },
            loadOffices() {
                const formationId = String(this.formationId || '');
                if (!formationId) {
                    this.offices = [];
                    return;
                }
                fetch(`/api/formations/${formationId}/offices`)
                    .then(r => r.json())
                    .then(data => {
                        const offices = Array.isArray(data) ? data : [];
                        this.offices = offices.filter(o => {
                            if (this.currentRole === 'Office Admin' && this.currentOfficeId) {
                                return String(o.id) === String(this.currentOfficeId);
                            }
                            if (this.currentRole === 'DCG' && this.currentDirectorateId) {
                                return String(o.directorate_id || '') === String(this.currentDirectorateId);
                            }
                            if (this.isShqSelected() && this.directorateId) {
                                return String(o.directorate_id || '') === String(this.directorateId);
                            }
                            return true;
                        });
                        if (this.officeId && !this.offices.some(o => String(o.id) === String(this.officeId))) {
                            this.officeId = '';
                        }
                    });
            },
            setTab(nextTab) {
                this.tab = nextTab;
                if (nextTab === 'custom') {
                    this.updateUrl(this.buildParams());
                    return;
                }
                this.fetchList();
            },
            setStatus(nextStatus) {
                this.status = nextStatus;
                this.fetchList();
            },
            chooseFormat(fmt) {
                this.format = fmt;
                this.exportMenuOpen = false;
                this.exportOpen = true;
            },
            selectAll() {
                this.fields = @js(array_values(array_filter(array_keys($exportFieldLabels), fn ($k) => $k !== 'merged_name')));
                if (this.mergeName) this.toggleMergeName(true);
            },
            clearAll() {
                this.fields = [];
                if (this.mergeName) this.toggleMergeName(true);
            },
            toggleMergeName(fromHelper = false) {
                if (this.mergeName) {
                    this.fields = this.fields.filter(f => !['surname','first_name','other_names','merged_name'].includes(f));
                    this.fields.splice(0, 0, 'merged_name');
                } else {
                    this.fields = this.fields.filter(f => f !== 'merged_name');
                    const needed = ['surname','first_name','other_names'];
                    const hasAny = needed.some(k => this.fields.includes(k));
                    if (!hasAny) this.fields = ['surname','first_name','other_names', ...this.fields];
                }
                if (!fromHelper) this.exportOpen = true;
            },
            toggleField(key) {
                if (this.fields.includes(key)) {
                    this.fields = this.fields.filter(f => f !== key);
                } else {
                    this.fields = [...this.fields, key];
                }
                if (this.mergeName) this.toggleMergeName(true);
            },
            dragStart(idx) {
                this.draggingIndex = idx;
            },
            dragEnter(idx) {
                this.dragOverIndex = idx;
            },
            dragEnd() {
                this.draggingIndex = null;
                this.dragOverIndex = null;
            },
            drop(idx) {
                if (this.draggingIndex === null || this.draggingIndex === idx) {
                    this.dragEnd();
                    return;
                }
                const arr = [...this.fields];
                const [item] = arr.splice(this.draggingIndex, 1);
                arr.splice(idx, 0, item);
                this.fields = arr;
                if (this.mergeName) this.toggleMergeName(true);
                this.dragEnd();
            }
        }"
        x-init="if (!formationId && formations.length === 1) { formationId = String(formations[0].id); } if (currentRole === 'DCG' && currentDirectorateId) { directorateId = String(currentDirectorateId); } loadOffices();"
    >
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="border-b border-gray-200">
                <nav class="-mb-px flex space-x-6 text-sm">
                    <button type="button" @click="setTab('list')" :class="tab === 'list' ? 'border-indigo-600 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'" class="border-b-2 py-2 px-1 font-medium">
                        Personnel List
                    </button>
                    <button type="button" @click="setTab('custom')" :class="tab === 'custom' ? 'border-indigo-600 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'" class="border-b-2 py-2 px-1 font-medium">
                        Custom Fields
                    </button>
                </nav>
            </div>

            <div x-show="tab === 'custom'" x-cloak class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <div class="flex flex-col gap-6 lg:flex-row lg:items-start lg:justify-between">
                        <div class="flex-1">
                            <div class="text-lg font-semibold text-gray-900">Custom Fields</div>
                            <div class="mt-1 text-sm text-gray-600">Add extra fields to the personnel registration form without changing the core form layout.</div>

                            <div class="mt-6 overflow-x-auto">
                                <table class="min-w-full border-collapse">
                                    <thead class="bg-gray-50">
                                        <tr class="text-xs font-semibold text-gray-700">
                                            <th class="px-4 py-3 text-left">Label</th>
                                            <th class="px-4 py-3 text-left">Key</th>
                                            <th class="px-4 py-3 text-left">Type</th>
                                            <th class="px-4 py-3 text-left">Required</th>
                                            @if(auth()->user()->isMainAdmin() || auth()->user()->isSuperAdmin())
                                                <th class="px-4 py-3 text-left">Action</th>
                                            @endif
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-200">
                                        @forelse($customFields as $field)
                                            <tr class="text-sm">
                                                <td class="px-4 py-3 font-semibold text-gray-900">{{ $field->label }}</td>
                                                <td class="px-4 py-3 text-gray-700">{{ $field->key }}</td>
                                                <td class="px-4 py-3 text-gray-700">{{ strtoupper($field->type) }}</td>
                                                <td class="px-4 py-3 text-gray-700">{{ $field->required ? 'Yes' : 'No' }}</td>
                                                @if(auth()->user()->isMainAdmin() || auth()->user()->isSuperAdmin())
                                                    <td class="px-4 py-3">
                                                        <div class="flex items-center gap-3">
                                                            <a href="{{ route('custom-fields.edit', $field) }}" class="text-indigo-600 hover:underline" @click.prevent="openModal('Edit Custom Field', '{{ route('custom-fields.edit', $field) }}')">
                                                                Edit
                                                            </a>
                                                            <form method="POST" action="{{ route('custom-fields.destroy', $field) }}" onsubmit="return confirm('Are you sure you want to delete this custom field? It will be removed from the form but existing data will be preserved.')">
                                                                @csrf
                                                                @method('DELETE')
                                                                <button type="submit" class="text-red-600 hover:underline">Delete</button>
                                                            </form>
                                                        </div>
                                                    </td>
                                                @endif
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="5" class="px-4 py-10 text-sm text-gray-500 text-center italic">No custom fields yet.</td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <div class="w-full max-w-lg">
                            <div class="p-4 rounded-lg border border-gray-200 bg-gray-50">
                                <div class="text-sm font-semibold text-gray-900">Field Types</div>
                                <div class="mt-2 text-sm text-gray-700 space-y-1">
                                    <div><span class="font-semibold">Text</span> – short text (e.g. ID, department)</div>
                                    <div><span class="font-semibold">Textarea</span> – long text (e.g. notes)</div>
                                    <div><span class="font-semibold">Number</span> – numeric values only</div>
                                    <div><span class="font-semibold">Date</span> – date picker</div>
                                    <div><span class="font-semibold">Select</span> – choose from a fixed list (comma-separated)</div>
                                </div>
                            </div>

                            @if(auth()->user()->isMainAdmin() || auth()->user()->isSuperAdmin())
                                <div class="mt-6 bg-white rounded-lg border border-gray-200">
                                    <div class="px-4 py-3 border-b border-gray-200 text-sm font-semibold text-gray-900">Add Custom Field</div>
                                    <form method="POST" action="{{ route('custom-fields.store') }}" class="p-4 space-y-4">
                                        @csrf

                                        <div>
                                            <x-input-label for="cf_label" :value="__('Label')" />
                                            <x-text-input id="cf_label" name="label" type="text" class="mt-1 block w-full" :value="old('label')" required />
                                            <x-input-error class="mt-2" :messages="$errors->get('label')" />
                                        </div>

                                        <div>
                                            <x-input-label for="cf_key" :value="__('Key (optional)')" />
                                            <x-text-input id="cf_key" name="key" type="text" class="mt-1 block w-full" :value="old('key')" placeholder="e.g. next_posting_date" />
                                            <div class="mt-1 text-xs text-gray-500">Lowercase letters, numbers, underscore. If blank, it will be generated from the label.</div>
                                            <x-input-error class="mt-2" :messages="$errors->get('key')" />
                                        </div>

                                        <div>
                                            <x-input-label for="cf_type" :value="__('Type')" />
                                            <select id="cf_type" name="type" class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm" required>
                                                <option value="text" {{ old('type') === 'text' ? 'selected' : '' }}>Text</option>
                                                <option value="textarea" {{ old('type') === 'textarea' ? 'selected' : '' }}>Textarea</option>
                                                <option value="number" {{ old('type') === 'number' ? 'selected' : '' }}>Number</option>
                                                <option value="date" {{ old('type') === 'date' ? 'selected' : '' }}>Date</option>
                                                <option value="select" {{ old('type') === 'select' ? 'selected' : '' }}>Select</option>
                                            </select>
                                            <x-input-error class="mt-2" :messages="$errors->get('type')" />
                                        </div>

                                        <div>
                                            <x-input-label for="cf_options" :value="__('Options (for Select)')" />
                                            <textarea id="cf_options" name="options" rows="2" class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm" placeholder="e.g. Yes,No">{{ old('options') }}</textarea>
                                            <x-input-error class="mt-2" :messages="$errors->get('options')" />
                                        </div>

                                        <div class="flex items-center gap-2">
                                            <input id="cf_required" name="required" type="checkbox" value="1" class="rounded border-gray-300 text-indigo-600" {{ old('required') ? 'checked' : '' }}>
                                            <label for="cf_required" class="text-sm text-gray-700">Required</label>
                                        </div>

                                        <button class="inline-flex items-center px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-md font-semibold">
                                            Create
                                        </button>
                                    </form>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            <div x-show="tab === 'list'" x-cloak class="space-y-6">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <button class="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-md shadow-sm">
                    <span x-text="status === 'incomplete' ? 'Incomplete Personnel' : 'Completed Personnel'"></span>
                </button>
                @if(auth()->user()->isMainAdmin() || auth()->user()->isSuperAdmin())
                    <a href="{{ route('personnel.inactive') }}" class="text-blue-600 text-sm font-medium hover:underline">
                        Posted Out / Exited
                    </a>
                @endif
            </div>

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <form method="GET" action="{{ route('personnel.index') }}" class="flex flex-col gap-3 lg:flex-row lg:items-center">
                        <div class="flex items-center gap-2 w-full">
                            <button type="button" class="h-10 w-12 flex items-center justify-center border border-gray-300 rounded-md bg-white" :class="hasActiveFilters() ? 'ring-2 ring-indigo-500' : ''" @click="filtersOpen = !filtersOpen">
                                <svg class="h-5 w-5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h10M4 18h16"></path>
                                </svg>
                            </button>

                            <div class="relative w-full">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-4.35-4.35m0 0A7.5 7.5 0 104.5 4.5a7.5 7.5 0 0012.15 12.15z"></path>
                                    </svg>
                                </div>
                                <input
                                    type="text"
                                    name="q"
                                    x-model="q"
                                    @input.debounce.300ms="onSearchInput()"
                                    placeholder="Search leads (Surname, NIS...)"
                                    class="h-10 w-full pl-10 pr-3 border border-gray-300 rounded-md focus:border-indigo-500 focus:ring-indigo-500"
                                />
                            </div>
                        </div>

                        <div class="flex items-center gap-2">
                            <a href="{{ route('personnel.index') }}" class="h-10 w-10 flex items-center justify-center border border-gray-300 rounded-md bg-white" title="Reset">
                                <svg class="h-5 w-5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v6h6M20 20v-6h-6M5.6 9A7 7 0 0119 12a7 7 0 01-13.4 3"></path>
                                </svg>
                            </a>
                            <button type="submit" class="h-10 w-10 flex items-center justify-center border border-gray-300 rounded-md bg-white" title="Search">
                                <svg class="h-5 w-5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-4.35-4.35m0 0A7.5 7.5 0 104.5 4.5a7.5 7.5 0 0012.15 12.15z"></path>
                                </svg>
                            </button>

                            <a href="{{ route('personnel.create') }}" class="h-10 inline-flex items-center px-4 bg-emerald-600 hover:bg-emerald-700 text-white rounded-md font-semibold">
                                New
                            </a>

                            @if(auth()->user()->isMainAdmin() || auth()->user()->isSuperAdmin())
                                <a href="{{ route('personnel.import') }}" class="h-10 inline-flex items-center px-4 bg-indigo-600 hover:bg-indigo-700 text-white rounded-md font-semibold">
                                    Import
                                </a>
                            @endif

                            <div class="relative" @click.outside="exportMenuOpen = false">
                            <button type="button" @click="exportMenuOpen = !exportMenuOpen" class="h-10 inline-flex items-center px-4 border border-gray-300 rounded-md bg-white font-semibold text-gray-700">
                                Export
                                <svg class="h-4 w-4 ml-2 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                </svg>
                            </button>
                                <div x-show="exportMenuOpen" x-transition x-cloak class="absolute right-0 mt-2 w-44 rounded-md bg-white shadow-lg border border-gray-200 overflow-hidden z-10">
                                    <button type="button" @click="chooseFormat('pdf')" class="w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">PDF</button>
                                    <button type="button" @click="chooseFormat('xls')" class="w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Excel</button>
                                    <button type="button" @click="chooseFormat('csv')" class="w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">CSV</button>
                                </div>
                            </div>
                        </div>
                    </form>

                    <div class="mt-4">
                        <div class="inline-flex w-full max-w-xl rounded-lg border border-blue-600 overflow-hidden bg-white">
                            <button type="button" class="flex-1 h-10 font-semibold" :class="status === 'completed' ? 'bg-blue-600 text-white' : 'bg-white text-blue-600'" @click="setStatus('completed')">
                                Completed
                            </button>
                            <button type="button" class="flex-1 h-10 font-semibold" :class="status === 'incomplete' ? 'bg-blue-600 text-white' : 'bg-white text-blue-600'" @click="setStatus('incomplete')">
                                Incomplete
                            </button>
                        </div>
                    </div>
                    
                    <div x-show="filtersOpen" x-cloak class="mt-4 p-4 border border-gray-200 rounded-lg bg-gray-50">
                        <div class="grid grid-cols-1 md:grid-cols-6 gap-4">
                            @if(auth()->user()->isMainAdmin() || auth()->user()->isSuperAdmin())
                                <div>
                                    <label class="text-xs font-semibold text-gray-700">Formation</label>
                                    <select class="mt-1 block w-full border-gray-300 rounded-md shadow-sm" x-model="formationId" @change="directorateId=''; officeId = ''; loadOffices(); applyFilters()">
                                        <option value="">All</option>
                                        <template x-for="f in formations" :key="f.id">
                                            <option :value="String(f.id)" x-text="(String(f.code || '').toUpperCase() === 'SHQ' || String(f.name || '').toLowerCase().includes('headquarters')) ? 'SHQ' : f.name"></option>
                                        </template>
                                    </select>
                                </div>
                            @endif
                            <div x-show="isShqSelected()" x-cloak>
                                <label class="text-xs font-semibold text-gray-700">Directorate</label>
                                <select class="mt-1 block w-full border-gray-300 rounded-md shadow-sm" x-model="directorateId" :disabled="currentRole === 'DCG'" @change="officeId=''; loadOffices(); applyFilters()">
                                    <option value="">All</option>
                                    <template x-for="d in directorates" :key="d.id">
                                        <option :value="String(d.id)" x-text="d.name"></option>
                                    </template>
                                </select>
                            </div>
                            <div>
                                <label class="text-xs font-semibold text-gray-700">Gender</label>
                                <select class="mt-1 block w-full border-gray-300 rounded-md shadow-sm" x-model="gender" @change="applyFilters()">
                                    <option value="">All</option>
                                    <option value="Male">Male</option>
                                    <option value="Female">Female</option>
                                </select>
                            </div>
                            <div>
                                <label class="text-xs font-semibold text-gray-700">Rank Code</label>
                                <select class="mt-1 block w-full border-gray-300 rounded-md shadow-sm" x-model="rankCode" @change="applyFilters()">
                                    <option value="">All</option>
                                    <template x-for="r in ranks" :key="r.code">
                                        <option :value="r.code" x-text="`${r.name} (${r.code})`"></option>
                                    </template>
                                </select>
                            </div>
                            <div>
                                <label class="text-xs font-semibold text-gray-700">Office</label>
                                <select class="mt-1 block w-full border-gray-300 rounded-md shadow-sm" x-model="officeId" @change="applyFilters()">
                                    <option value="">All</option>
                                    <template x-for="o in offices" :key="o.id">
                                        <option :value="String(o.id)" x-text="o.name"></option>
                                    </template>
                                </select>
                            </div>
                            <div>
                                <label class="text-xs font-semibold text-gray-700">DOPA</label>
                                <input type="date" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm" x-model="dopa" @change="applyFilters()">
                            </div>
                            <div>
                                <label class="text-xs font-semibold text-gray-700">DOPP</label>
                                <input type="date" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm" x-model="dopp" @change="applyFilters()">
                            </div>
                        </div>
                        <div class="mt-4 flex items-center justify-end gap-2">
                            <button type="button" class="h-9 inline-flex items-center px-3 border border-gray-300 rounded-md bg-white font-semibold text-gray-700" @click="resetFilters()">
                                Reset
                            </button>
                        </div>
                    </div>

                    <div class="mt-4 text-sm text-gray-500" x-show="loading" x-cloak>
                        Loading...
                    </div>

                    <div id="personnelTable">
                        @include('personnel.partials.table', ['personnel' => $personnel])
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div x-show="exportOpen" x-transition x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-black/40" @click="exportOpen = false"></div>
        <div class="relative w-[33.33vw] max-w-[95vw] bg-white rounded-lg shadow-lg flex flex-col max-h-[33.33vh]">
            <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between shrink-0">
                <div class="text-lg font-semibold text-gray-900">
                    Export Personnel
                    <span class="text-sm font-normal text-gray-500" x-text="format.toUpperCase()"></span>
                </div>
                <button type="button" @click="exportOpen = false" class="text-gray-500 hover:text-gray-700">
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>

            <div class="overflow-y-auto flex-1">
            <form method="GET" action="{{ route('personnel.export') }}" class="p-6 space-y-6">
                <input type="hidden" name="q" :value="q">
                <input type="hidden" name="gender" :value="gender">
                <input type="hidden" name="rank_code" :value="rankCode">
                <input type="hidden" name="office_id" :value="officeId">
                <input type="hidden" name="formation_id" :value="formationId">
                <input type="hidden" name="directorate_id" :value="directorateId">
                <input type="hidden" name="dopa" :value="dopa">
                <input type="hidden" name="dopp" :value="dopp">
                    <input type="hidden" name="merge_name" :value="mergeName ? 1 : 0">
                    <input type="hidden" name="format" :value="format">
                    <template x-for="f in fields" :key="f">
                        <input type="hidden" name="fields[]" :value="f">
                    </template>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <div class="md:col-span-1 space-y-3">
                            <div class="pt-3 border-t border-gray-200 space-y-2">
                                <div class="text-sm font-semibold text-gray-900">Name Option</div>
                                <label class="flex items-start gap-2 text-sm">
                                    <input type="checkbox" class="mt-1 rounded border-gray-300 text-indigo-600" x-model="mergeName" @change="toggleMergeName(true)">
                                    <span>
                                        Merge name (dashboard format)
                                        <span class="block text-xs text-gray-500">Uses the same initials/case formatting used on the personnel dashboard.</span>
                                    </span>
                                </label>
                            </div>
                        </div>

                        <div class="md:col-span-2 space-y-3">
                            <div class="flex items-center justify-between">
                                <div class="text-sm font-semibold text-gray-900">Fields to export</div>
                                <div class="flex items-center gap-3 text-sm">
                                    <button type="button" class="text-indigo-600 hover:underline" @click="selectAll()">Select all</button>
                                    <button type="button" class="text-gray-600 hover:underline" @click="clearAll()">Clear</button>
                                </div>
                            </div>

                            <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
                                <div class="border border-gray-200 rounded-md p-4">
                                    <div class="text-sm font-semibold text-gray-900">Selected Order (drag & drop)</div>
                                    <div class="mt-3 space-y-2 max-h-[260px] overflow-y-auto">
                                        <template x-for="(f, idx) in fields" :key="f">
                                            <div
                                                class="flex items-center justify-between gap-3 border border-gray-200 rounded-md px-3 py-2 bg-white"
                                                :class="dragOverIndex === idx ? 'ring-2 ring-indigo-500' : ''"
                                                :draggable="f !== 'merged_name'"
                                                @dragstart="dragStart(idx)"
                                                @dragenter.prevent="dragEnter(idx)"
                                                @dragover.prevent
                                                @dragend="dragEnd()"
                                                @drop.prevent="drop(idx)"
                                            >
                                                <div class="text-sm text-gray-900 font-semibold" x-text="exportFieldLabels[f] || f"></div>
                                                <button type="button" class="text-sm text-gray-600 hover:underline" @click="if (f !== 'merged_name') toggleField(f)">
                                                    Remove
                                                </button>
                                            </div>
                                        </template>
                                        <div x-show="fields.length === 0" class="text-sm text-gray-500 italic">
                                            No fields selected.
                                        </div>
                                    </div>
                                </div>

                                <div class="border border-gray-200 rounded-md p-4">
                                    <div class="text-sm font-semibold text-gray-900">Available Fields</div>
                                    <div class="mt-3 grid grid-cols-1 sm:grid-cols-2 gap-2 max-h-[260px] overflow-y-auto">
                                        @foreach($exportFieldLabels as $key => $label)
                                            @if($key !== 'merged_name')
                                                <label class="flex items-center gap-2 text-sm">
                                                    <input type="checkbox" class="rounded border-gray-300 text-indigo-600" :checked="fields.includes('{{ $key }}')" @change="toggleField('{{ $key }}')">
                                                    <span>{{ $label }}</span>
                                                </label>
                                            @endif
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="flex items-center justify-end gap-3 pt-4">
                        <button type="button" class="h-10 inline-flex items-center px-4 border border-gray-300 rounded-md bg-white font-semibold text-gray-700" @click="exportOpen = false">
                            Cancel
                        </button>
                        <button type="submit" class="h-10 inline-flex items-center px-4 bg-indigo-600 hover:bg-indigo-700 text-white rounded-md font-semibold">
                            Export
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <div x-show="modalOpen" x-cloak class="fixed inset-0 z-50 overflow-y-auto">
        <div class="absolute inset-0 bg-black/50" x-on:click="closeModal()"></div>
        <div class="relative min-h-screen flex items-center justify-center p-4">
            <div class="bg-white w-[33.33vw] max-w-[95vw] rounded-lg shadow-lg overflow-hidden flex flex-col" :style="{ maxHeight: '33.33vh' }">
                <div class="flex items-center justify-between px-4 py-3 border-b border-gray-200 shrink-0">
                    <div class="font-semibold text-gray-900" x-text="modalTitle"></div>
                    <button type="button" class="text-gray-600 hover:text-gray-900" x-on:click="closeModal()">
                        Close
                    </button>
                </div>
                <div class="flex-1 overflow-y-auto">
                    <iframe id="modalIframe" class="w-full" :src="modalUrl" frameborder="0"></iframe>
                </div>
            </div>
        </div>
    </div>
    <script>
        window.addEventListener('message', (event) => {
            if (event.data && event.data.type === 'modalHeight') {
                const iframe = document.getElementById('modalIframe');
                if (iframe) {
                    iframe.style.height = event.data.height + 'px';
                }
            }
        });
    </script>
        </div>
    </div>
</x-app-layout>
