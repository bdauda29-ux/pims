@php
    $dashboardMode = session('dashboard_mode', 'personal');
    $canAdminMode = auth()->check() && auth()->user()->isPersonnel() && ! auth()->user()->isUser();
    $isAdminUi = $canAdminMode && $dashboardMode === 'admin';
    $navBg = ($isAdminUi || auth()->user()->isMainAdmin() || auth()->user()->isSuperAdmin() || auth()->user()->isDCG() || auth()->user()->isPSO() || auth()->user()->isFormationAdmin())
        ? 'bg-[#7B5A2D]'
        : 'bg-green-700';
    $canStructure = auth()->check() && (
        auth()->user()->hasAbility('formations.manage')
        || auth()->user()->hasAbility('directorates.manage')
        || auth()->user()->hasAbility('offices.manage')
    );
    $dashboardHref = route('dashboard');
@endphp
<nav x-data="{ open: false }" class="{{ $navBg }} border-b border-transparent">
    <!-- Primary Navigation Menu -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16">
            <div class="flex">
                <!-- Logo -->
                <div class="shrink-0 flex items-center">
                    <a href="{{ $dashboardHref }}">
                        <x-application-logo class="block h-9 w-auto" />
                    </a>
                </div>

                <!-- Navigation Links -->
                <div class="hidden space-x-8 sm:-my-px sm:ms-10 sm:flex">
                    <x-nav-link variant="light" :href="$dashboardHref" :active="request()->routeIs('dashboard') || request()->routeIs('admin.portal')">
                        {{ __('Dashboard') }}
                    </x-nav-link>

                        @if(auth()->user()->isMainAdmin() || auth()->user()->isSuperAdmin() || $canStructure)
                            @if(auth()->user()->isMainAdmin())
                                <x-nav-link variant="light" :href="route('management.index')" :active="request()->routeIs('management.*')">
                                    {{ __('Management') }}
                                </x-nav-link>

                                @php
                                    $personnelActive = request()->routeIs('personnel.index')
                                        || request()->routeIs('personnel.inactive')
                                        || request()->routeIs('personnel.promotions');
                                    $personnelTriggerClass = $personnelActive
                                        ? 'inline-flex items-center h-full gap-1 px-1 pt-1 border-b-2 border-white/80 text-sm font-medium leading-5 text-white focus:outline-none focus:border-white transition duration-150 ease-in-out'
                                        : 'inline-flex items-center h-full gap-1 px-1 pt-1 border-b-2 border-transparent text-sm font-medium leading-5 text-white/90 hover:text-white hover:border-white/60 focus:outline-none focus:text-white focus:border-white/70 transition duration-150 ease-in-out';
                                @endphp

                                <x-dropdown align="left" width="48" contentClasses="py-1 bg-white" offset="top-40">
                                    <x-slot name="trigger">
                                        <button type="button" class="{{ $personnelTriggerClass }}">
                                            {{ __('Personnel') }}
                                            <svg class="h-4 w-4 fill-current opacity-90" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                            </svg>
                                        </button>
                                    </x-slot>
                                    <x-slot name="content">
                                        <x-dropdown-link :href="route('personnel.index')">
                                            {{ __('Active Personnel') }}
                                        </x-dropdown-link>
                                        <x-dropdown-link :href="route('personnel.inactive')">
                                            {{ __('Inactive Personnel') }}
                                        </x-dropdown-link>
                                        <x-dropdown-link :href="route('personnel.promotions')">
                                            {{ __('Promotion') }}
                                        </x-dropdown-link>
                                    </x-slot>
                                </x-dropdown>
                            @endif

                            @php
                                $structureActive = request()->routeIs('formations.*')
                                    || request()->routeIs('directorates.*')
                                    || request()->routeIs('offices.*');
                                $structureTriggerClass = $structureActive
                                    ? 'inline-flex items-center h-full gap-1 px-1 pt-1 border-b-2 border-white/80 text-sm font-medium leading-5 text-white focus:outline-none focus:border-white transition duration-150 ease-in-out'
                                    : 'inline-flex items-center h-full gap-1 px-1 pt-1 border-b-2 border-transparent text-sm font-medium leading-5 text-white/90 hover:text-white hover:border-white/60 focus:outline-none focus:text-white focus:border-white/70 transition duration-150 ease-in-out';
                            @endphp

                            <x-dropdown align="left" width="48" contentClasses="py-1 bg-white" offset="top-80">
                                <x-slot name="trigger">
                                    <button type="button" class="{{ $structureTriggerClass }}">
                                        {{ __('Structure') }}
                                        <svg class="h-4 w-4 fill-current opacity-90" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                        </svg>
                                    </button>
                                </x-slot>
                                <x-slot name="content">
                                    @if(auth()->user()->hasAbility('directorates.manage'))
                                        <x-dropdown-link :href="route('directorates.index')">
                                            {{ __('Directorates') }}
                                        </x-dropdown-link>
                                    @endif
                                    @if(auth()->user()->hasAbility('formations.manage'))
                                        <x-dropdown-link :href="route('formations.index')">
                                            {{ __('Formations') }}
                                        </x-dropdown-link>
                                    @endif
                                    @if(auth()->user()->hasAbility('offices.manage'))
                                        <x-dropdown-link :href="route('offices.index')">
                                            {{ __('Offices') }}
                                        </x-dropdown-link>
                                        <x-dropdown-link :href="route('admin.roles.index')">
                                            {{ __('Office Admin Roles') }}
                                        </x-dropdown-link>
                                    @endif
                                </x-slot>
                            </x-dropdown>

                            @if(auth()->user()->isMainAdmin())
                                @php
                                    $controlActive = request()->routeIs('management.standalone-roles')
                                        || request()->routeIs('management.standalone.*')
                                        || request()->routeIs('privileges.*')
                                        || request()->routeIs('audit-logs.*');
                                    $controlTriggerClass = $controlActive
                                        ? 'inline-flex items-center h-full gap-1 px-1 pt-1 border-b-2 border-white/80 text-sm font-medium leading-5 text-white focus:outline-none focus:border-white transition duration-150 ease-in-out'
                                        : 'inline-flex items-center h-full gap-1 px-1 pt-1 border-b-2 border-transparent text-sm font-medium leading-5 text-white/90 hover:text-white hover:border-white/60 focus:outline-none focus:text-white focus:border-white/70 transition duration-150 ease-in-out';
                                @endphp

                                <x-dropdown align="left" width="48" contentClasses="py-1 bg-white" offset="top-40">
                                    <x-slot name="trigger">
                                        <button type="button" class="{{ $controlTriggerClass }}">
                                            {{ __('Control') }}
                                            <svg class="h-4 w-4 fill-current opacity-90" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                            </svg>
                                        </button>
                                    </x-slot>
                                    <x-slot name="content">
                                        <x-dropdown-link :href="route('management.standalone-roles')">
                                            {{ __('Standalone Admins') }}
                                        </x-dropdown-link>
                                        <x-dropdown-link :href="route('privileges.index')">
                                            {{ __('Privileges') }}
                                        </x-dropdown-link>
                                        <x-dropdown-link :href="route('audit-logs.index')">
                                            {{ __('Audit Log') }}
                                        </x-dropdown-link>
                                    </x-slot>
                                </x-dropdown>
                            @endif

                            @if(auth()->user()->isSuperAdmin())
                                <x-nav-link variant="light" :href="route('admin.users.index')" :active="request()->routeIs('admin.users.*')">
                                    {{ __('Admins') }}
                                </x-nav-link>
                            @endif
                        @endif
                </div>
            </div>

            <!-- Settings Dropdown -->
            <div class="hidden sm:flex sm:items-center sm:ms-6">
                <x-dropdown align="right" width="48">
                    <x-slot name="trigger">
                        <button class="inline-flex items-center px-3 py-2 border border-white/30 text-sm leading-4 font-medium rounded-md text-white bg-transparent hover:bg-white/10 focus:outline-none transition ease-in-out duration-150">
                            <div>{{ Auth::user()->name }}</div>

                            <div class="ms-1">
                                <svg class="fill-current h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                </svg>
                            </div>
                        </button>
                    </x-slot>

                    <x-slot name="content">
                        <x-dropdown-link :href="route('profile.edit')">
                            {{ __('Profile') }}
                        </x-dropdown-link>

                        <button type="button" @click="openChangePassword = true" class="block w-full px-4 py-2 text-start text-sm leading-5 text-gray-700 hover:bg-gray-100 focus:outline-none focus:bg-gray-100 transition duration-150 ease-in-out">
                            {{ __('Change Password') }}
                        </button>

                        @if($canAdminMode)
                            <form method="POST" action="{{ route('dashboard.mode') }}">
                                @csrf
                                <input type="hidden" name="mode" value="{{ $isAdminUi ? 'personal' : 'admin' }}">
                                <button type="submit" class="block w-full px-4 py-2 text-start text-sm leading-5 text-gray-700 hover:bg-gray-100 focus:outline-none focus:bg-gray-100 transition duration-150 ease-in-out">
                                    {{ $isAdminUi ? __('Switch to Personal') : __('Switch to Admin') }}
                                </button>
                            </form>
                        @endif

                        <!-- Authentication -->
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf

                            <x-dropdown-link :href="route('logout')"
                                    onclick="event.preventDefault();
                                                this.closest('form').submit();">
                                {{ __('Log Out') }}
                            </x-dropdown-link>
                        </form>
                    </x-slot>
                </x-dropdown>
            </div>

            <!-- Hamburger -->
            <div class="-me-2 flex items-center sm:hidden">
                <button @click="open = ! open" class="inline-flex items-center justify-center p-2 rounded-md text-white hover:bg-white/10 focus:outline-none transition duration-150 ease-in-out">
                    <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                        <path :class="{'hidden': open, 'inline-flex': ! open }" class="inline-flex" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        <path :class="{'hidden': ! open, 'inline-flex': open }" class="hidden" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </div>
    </div>

    <!-- Responsive Navigation Menu -->
    <div :class="{'block': open, 'hidden': ! open}" class="hidden sm:hidden">
        <div class="pt-2 pb-3 space-y-1">
            <x-responsive-nav-link variant="light" :href="$dashboardHref" :active="request()->routeIs('dashboard') || request()->routeIs('admin.portal')">
                {{ __('Dashboard') }}
            </x-responsive-nav-link>

            @if(auth()->user()->isMainAdmin() || auth()->user()->isSuperAdmin() || $canStructure)
                @if(auth()->user()->isMainAdmin())
                    <x-responsive-nav-link variant="light" :href="route('management.index')" :active="request()->routeIs('management.*')">
                        {{ __('Management') }}
                    </x-responsive-nav-link>
                        <div class="pt-2 pb-1 px-3 text-xs font-semibold text-white/80 uppercase tracking-widest">
                            {{ __('Personnel') }}
                        </div>
                        <x-responsive-nav-link variant="light" :href="route('personnel.index')" :active="request()->routeIs('personnel.index')">
                            {{ __('Active Personnel') }}
                        </x-responsive-nav-link>
                        <x-responsive-nav-link variant="light" :href="route('personnel.inactive')" :active="request()->routeIs('personnel.inactive')">
                            {{ __('Inactive Personnel') }}
                        </x-responsive-nav-link>
                        <x-responsive-nav-link variant="light" :href="route('personnel.promotions')" :active="request()->routeIs('personnel.promotions')">
                            {{ __('Promotion') }}
                        </x-responsive-nav-link>
                @endif

                <div class="pt-2 pb-1 px-3 text-xs font-semibold text-white/80 uppercase tracking-widest">
                    {{ __('Structure') }}
                </div>
                @if(auth()->user()->hasAbility('directorates.manage'))
                    <x-responsive-nav-link variant="light" :href="route('directorates.index')" :active="request()->routeIs('directorates.*')">
                        {{ __('Directorates') }}
                    </x-responsive-nav-link>
                @endif
                @if(auth()->user()->hasAbility('formations.manage'))
                    <x-responsive-nav-link variant="light" :href="route('formations.index')" :active="request()->routeIs('formations.*')">
                        {{ __('Formations') }}
                    </x-responsive-nav-link>
                @endif
                @if(auth()->user()->hasAbility('offices.manage'))
                    <x-responsive-nav-link variant="light" :href="route('offices.index')" :active="request()->routeIs('offices.*')">
                        {{ __('Offices') }}
                    </x-responsive-nav-link>
                    <x-responsive-nav-link variant="light" :href="route('admin.roles.index')" :active="request()->routeIs('admin.roles.*')">
                        {{ __('Office Admin Roles') }}
                    </x-responsive-nav-link>
                @endif

                @if(auth()->user()->isMainAdmin())
                    <div class="pt-2 pb-1 px-3 text-xs font-semibold text-white/80 uppercase tracking-widest">
                        {{ __('Control') }}
                    </div>
                    <x-responsive-nav-link variant="light" :href="route('management.standalone-roles')" :active="request()->routeIs('management.standalone-roles') || request()->routeIs('management.standalone.*')">
                        {{ __('Standalone Admins') }}
                    </x-responsive-nav-link>
                    <x-responsive-nav-link variant="light" :href="route('privileges.index')" :active="request()->routeIs('privileges.*')">
                        {{ __('Privileges') }}
                    </x-responsive-nav-link>
                    <x-responsive-nav-link variant="light" :href="route('audit-logs.index')" :active="request()->routeIs('audit-logs.*')">
                        {{ __('Audit Log') }}
                    </x-responsive-nav-link>
                @endif

                @if(auth()->user()->isSuperAdmin())
                    <x-responsive-nav-link variant="light" :href="route('admin.users.index')" :active="request()->routeIs('admin.users.*')">
                        {{ __('Admins') }}
                    </x-responsive-nav-link>
                @endif
            @endif
        </div>

        <!-- Responsive Settings Options -->
        <div class="pt-4 pb-1 border-t border-white/30">
            <div class="px-4">
                <div class="font-medium text-base text-white">{{ Auth::user()->name }}</div>
                <div class="font-medium text-sm text-white/90">{{ Auth::user()->email }}</div>
            </div>

            <div class="mt-3 space-y-1">
                <x-responsive-nav-link variant="light" :href="route('profile.edit')">
                    {{ __('Profile') }}
                </x-responsive-nav-link>

                @if($canAdminMode)
                    <form method="POST" action="{{ route('dashboard.mode') }}">
                        @csrf
                        <input type="hidden" name="mode" value="{{ $isAdminUi ? 'personal' : 'admin' }}">
                        <button type="submit" class="block w-full ps-3 pe-4 py-2 text-start text-base font-medium text-white/90 hover:text-white hover:bg-white/10 focus:outline-none transition duration-150 ease-in-out">
                            {{ $isAdminUi ? __('Switch to Personal') : __('Switch to Admin') }}
                        </button>
                    </form>
                @endif

                <!-- Authentication -->
                <form method="POST" action="{{ route('logout') }}">
                    @csrf

                    <x-responsive-nav-link variant="light" :href="route('logout')"
                            onclick="event.preventDefault();
                                        this.closest('form').submit();">
                        {{ __('Log Out') }}
                    </x-responsive-nav-link>
                </form>
            </div>
        </div>
    </div>
</nav>
