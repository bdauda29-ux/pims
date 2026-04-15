@php
    $layout = request()->boolean('modal') ? 'modal-layout' : 'app-layout';
@endphp
<x-dynamic-component :component="$layout">
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Formation Admins') }} - {{ $formation->name }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if(session('success'))
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-4 text-sm text-green-700">
                        {{ session('success') }}
                    </div>
                </div>
            @endif
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <div class="flex items-center justify-between gap-4">
                        <div class="text-sm text-gray-600">Only one Formation Admin is allowed per formation.</div>
                        <a href="{{ route('management.formations.admins.create', $formation) }}" class="inline-flex items-center px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-md font-semibold">
                            Add Admin
                        </a>
                    </div>

                    <div class="mt-6 overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">NIS</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Email</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Action</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @forelse($admins as $admin)
                                    <tr>
                                        <td class="px-4 py-3 text-sm text-gray-900 font-semibold">{{ $admin->name }}</td>
                                        <td class="px-4 py-3 text-sm text-gray-700">{{ $admin->nis_no }}</td>
                                        <td class="px-4 py-3 text-sm text-gray-700">{{ $admin->email }}</td>
                                        <td class="px-4 py-3 text-sm">
                                            <div class="flex items-center gap-3">
                                                <form method="POST" action="{{ route('management.users.reset-password', $admin) }}" onsubmit="return confirm('Reset password for this admin?')">
                                                    @csrf
                                                    <button type="submit" class="text-indigo-600 font-semibold hover:underline">
                                                        Reset Password
                                                    </button>
                                                </form>
                                                <form method="POST" action="{{ route('management.users.delete', $admin) }}" onsubmit="return confirm('Delete this admin?')">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="text-red-600 font-semibold hover:underline">
                                                        Delete
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="px-4 py-6 text-sm text-gray-500 text-center">No Formation Admin assigned.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-6">
                        <a href="{{ route('management.index') }}" class="text-indigo-600 font-semibold hover:underline">Back to Management</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-dynamic-component>
