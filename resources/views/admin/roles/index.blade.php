<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Office Admin Roles') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <div class="text-lg font-semibold text-gray-900">Convert Personnel</div>
                            <div class="text-sm text-gray-600">Change role between User and Office Admin.</div>
                        </div>

                        <form method="GET" action="{{ route('admin.roles.index') }}" class="flex items-center gap-2">
                            <input name="q" value="{{ request('q') }}" placeholder="Search name / NIS / email" class="border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm w-72 max-w-full" />
                            <button class="inline-flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-md font-semibold">
                                Search
                            </button>
                        </form>
                    </div>

                    @if(session('success'))
                        <div class="mt-4 p-3 rounded-md bg-green-50 text-green-800 text-sm border border-green-200">
                            {{ session('success') }}
                        </div>
                    @endif

                    <div class="mt-6 overflow-x-auto">
                        <table class="min-w-full border-collapse">
                            <thead class="bg-gray-50">
                                <tr class="text-xs font-semibold text-gray-700">
                                    <th class="px-4 py-3 text-left">Personnel</th>
                                    <th class="px-4 py-3 text-left">Formation</th>
                                    <th class="px-4 py-3 text-left">Current Role</th>
                                    <th class="px-4 py-3 text-left">Office</th>
                                    <th class="px-4 py-3 text-left">Action</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200">
                                @forelse($users as $u)
                                    <tr class="text-sm">
                                        <td class="px-4 py-3">
                                            <div class="font-semibold text-gray-900">{{ $u->nameTag() }}</div>
                                            <div class="text-xs text-gray-600">{{ $u->nis_no }} • {{ $u->email }}</div>
                                        </td>
                                        <td class="px-4 py-3">{{ $u->formation?->name ?? '-' }}</td>
                                        <td class="px-4 py-3">
                                            <span class="inline-flex items-center px-2 py-1 rounded bg-gray-100 text-gray-800 text-xs font-semibold">
                                                {{ $u->role }}
                                            </span>
                                        </td>
                                        <td class="px-4 py-3">{{ $u->office?->name ?? '-' }}</td>
                                        <td class="px-4 py-3">
                                            <form method="POST" action="{{ route('admin.roles.update', $u) }}" class="flex flex-col gap-2 sm:flex-row sm:items-center">
                                                @csrf
                                                @method('PATCH')

                                                <select name="role" class="border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
                                                    <option value="User" {{ old('role') === 'User' ? 'selected' : '' }} {{ $u->role === 'User' ? 'selected' : '' }}>User</option>
                                                    <option value="Office Admin" {{ old('role') === 'Office Admin' ? 'selected' : '' }} {{ $u->role === 'Office Admin' ? 'selected' : '' }}>Office Admin</option>
                                                </select>

                                                <select name="office_id" class="border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
                                                    <option value="">Select Office</option>
                                                    @foreach($offices as $office)
                                                        @if((auth()->user()->isDCG() || auth()->user()->isPSO()) && $u->directorate_id && (int) $office->directorate_id === (int) $u->directorate_id)
                                                            <option value="{{ $office->id }}" {{ (string) old('office_id', $u->office_id) === (string) $office->id ? 'selected' : '' }}>
                                                                {{ $office->name }}
                                                            </option>
                                                        @elseif($u->formation_id && (int) $office->formation_id === (int) $u->formation_id)
                                                            <option value="{{ $office->id }}" {{ (string) old('office_id', $u->office_id) === (string) $office->id ? 'selected' : '' }}>
                                                                {{ $office->name }}
                                                            </option>
                                                        @endif
                                                    @endforeach
                                                </select>

                                                <button class="inline-flex items-center px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-md font-semibold">
                                                    Update
                                                </button>
                                            </form>

                                            @if($errors->any())
                                                <div class="mt-2 text-xs text-red-600">
                                                    @foreach($errors->all() as $error)
                                                        <div>{{ $error }}</div>
                                                    @endforeach
                                                </div>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="px-4 py-10 text-sm text-gray-500 text-center italic">
                                            No personnel found.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-6">
                        {{ $users->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
