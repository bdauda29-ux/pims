<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Admin') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <div class="text-lg font-semibold text-gray-900">Admin Workspace</div>
                    <div class="mt-1 text-sm text-gray-600">Use the links below to manage the system.</div>

                    <div class="mt-6 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                        @if(auth()->user()->isMainAdmin())
                            <a href="{{ route('management.index') }}" class="block p-4 rounded-lg border border-gray-200 hover:border-gray-300 hover:bg-gray-50">
                                <div class="font-semibold text-gray-900">Management</div>
                                <div class="text-sm text-gray-600">Tree view + admins + rank charts</div>
                            </a>
                        @endif
                        @if(!auth()->user()->isSuperAdmin())
                            <a href="{{ route('personnel.index') }}" class="block p-4 rounded-lg border border-gray-200 hover:border-gray-300 hover:bg-gray-50">
                                <div class="font-semibold text-gray-900">Personnel</div>
                                <div class="text-sm text-gray-600">View personnel list</div>
                            </a>

                            @if(!auth()->user()->isMainAdmin())
                                <a href="{{ route('personnel.create') }}" class="block p-4 rounded-lg border border-gray-200 hover:border-gray-300 hover:bg-gray-50">
                                    <div class="font-semibold text-gray-900">New Personnel</div>
                                    <div class="text-sm text-gray-600">Create a new personnel record</div>
                                </a>
                            @endif

                            <a href="{{ route('offices.index') }}" class="block p-4 rounded-lg border border-gray-200 hover:border-gray-300 hover:bg-gray-50">
                                <div class="font-semibold text-gray-900">Offices</div>
                                <div class="text-sm text-gray-600">Manage offices</div>
                            </a>
                        @endif

                        @if(auth()->user()->isMainAdmin() || auth()->user()->isSuperAdmin())
                            <a href="{{ route('formations.index') }}" class="block p-4 rounded-lg border border-gray-200 hover:border-gray-300 hover:bg-gray-50">
                                <div class="font-semibold text-gray-900">Formations</div>
                                <div class="text-sm text-gray-600">Manage formations</div>
                            </a>

                            <a href="{{ route('directorates.index') }}" class="block p-4 rounded-lg border border-gray-200 hover:border-gray-300 hover:bg-gray-50">
                                <div class="font-semibold text-gray-900">Directorates</div>
                                <div class="text-sm text-gray-600">View directorates</div>
                            </a>
                        @endif

                        @if(auth()->user()->isMainAdmin())
                            <a href="{{ route('admin.roles.index') }}" class="block p-4 rounded-lg border border-gray-200 hover:border-gray-300 hover:bg-gray-50">
                                <div class="font-semibold text-gray-900">Role Management</div>
                                <div class="text-sm text-gray-600">Convert personnel user ⇄ office admin</div>
                            </a>

                            <a href="{{ route('privileges.index') }}" class="block p-4 rounded-lg border border-gray-200 hover:border-gray-300 hover:bg-gray-50">
                                <div class="font-semibold text-gray-900">Privileges</div>
                                <div class="text-sm text-gray-600">Control access by role</div>
                            </a>

                            <a href="{{ route('audit-logs.index') }}" class="block p-4 rounded-lg border border-gray-200 hover:border-gray-300 hover:bg-gray-50">
                                <div class="font-semibold text-gray-900">Audit Logs</div>
                                <div class="text-sm text-gray-600">Review activity logs</div>
                            </a>
                        @endif

                        @if(auth()->user()->isSuperAdmin())
                            <a href="{{ route('admin.users.index') }}" class="block p-4 rounded-lg border border-gray-200 hover:border-gray-300 hover:bg-gray-50">
                                <div class="font-semibold text-gray-900">Main Admin Setup</div>
                                <div class="text-sm text-gray-600">Promote users to Main Admin</div>
                            </a>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
