@php
    $myNotifications = auth()->user()->quotationNotifications()
        ->orderByRaw('read_at IS NOT NULL')
        ->orderByDesc('sent_at')
        ->limit(10)
        ->get();
    $unreadNotificationsCount = auth()->user()->quotationNotifications()->whereNull('read_at')->count();
@endphp

<nav x-data="{ open: false }" class="bg-white border-b border-gray-100">
    <!-- Primary Navigation Menu -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16">
            <div class="flex">
                <!-- Logo -->
                <div class="shrink-0 flex items-center">
                    <a href="{{ route('dashboard') }}">
                        <x-application-logo class="h-8" />
                    </a>
                </div>

                <!-- Navigation Links -->
                <div class="hidden space-x-8 sm:-my-px sm:ms-10 sm:flex">
                    <x-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')">
                        {{ __('Dashboard') }}
                    </x-nav-link>

                    @canany(['quotations.view.all', 'quotations.view.search'])
                        <x-nav-link :href="route('quotations.index')" :active="request()->routeIs('quotations.index')">
                            {{ __('Cotizaciones') }}
                        </x-nav-link>
                    @endcanany

                    @can('quotations.assign')
                        <x-nav-link :href="route('assignments.index')" :active="request()->routeIs('assignments.index')">
                            {{ __('Panel de asignación') }}
                        </x-nav-link>
                    @endcan

                    @can('quotations.view.assigned')
                        <x-nav-link :href="route('my-quotations.index')" :active="request()->routeIs('my-quotations.index')">
                            {{ __('Mis cotizaciones') }}
                        </x-nav-link>
                    @endcan

                    @can('duplicates.view')
                        <x-nav-link :href="route('duplicates.index')" :active="request()->routeIs('duplicates.index')">
                            {{ __('Duplicados') }}
                        </x-nav-link>
                    @endcan

                    @can('reports.view')
                        <x-nav-link :href="route('reports.index')" :active="request()->routeIs('reports.index')">
                            {{ __('Reportes') }}
                        </x-nav-link>
                    @endcan

                    @canany(['facades.view', 'facades.view.all'])
                        <x-nav-link :href="route('facades.index')" :active="request()->routeIs('facades.*')">
                            {{ __('Fachadas') }}
                        </x-nav-link>
                    @endcanany

                    @can('users.manage')
                        <x-nav-link :href="route('users.index')" :active="request()->routeIs('users.index')">
                            {{ __('Usuarios') }}
                        </x-nav-link>
                    @endcan
                </div>
            </div>

            <!-- Settings Dropdown -->
            <div class="hidden sm:flex sm:items-center sm:ms-6 sm:gap-2">
                <x-dropdown align="right" width="w-96" content-classes="py-1 bg-white max-h-96 overflow-y-auto">
                    <x-slot name="trigger">
                        <button class="relative inline-flex items-center p-2 border border-transparent rounded-md text-gray-500 hover:text-gray-700 focus:outline-none transition ease-in-out duration-150">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                            </svg>
                            @if ($unreadNotificationsCount > 0)
                                <span class="absolute -top-0.5 -right-0.5 inline-flex items-center justify-center px-1.5 py-0.5 rounded-full text-xs font-semibold bg-red-500 text-white">
                                    {{ $unreadNotificationsCount > 9 ? '9+' : $unreadNotificationsCount }}
                                </span>
                            @endif
                        </button>
                    </x-slot>

                    <x-slot name="content">
                        <div class="flex items-center justify-between px-4 py-2 border-b border-gray-100">
                            <span class="text-sm font-semibold text-gray-800">{{ __('Notificaciones') }}</span>
                            @if ($unreadNotificationsCount > 0)
                                <form method="POST" action="{{ route('notifications.mark-all-read') }}">
                                    @csrf
                                    <button type="submit" class="text-xs text-procon hover:text-procon-dark">
                                        {{ __('Marcar todas como leídas') }}
                                    </button>
                                </form>
                            @endif
                        </div>

                        @forelse ($myNotifications as $notification)
                            <a href="{{ route('notifications.open', $notification) }}"
                               class="block px-4 py-2 text-start text-sm leading-5 hover:bg-gray-100 transition duration-150 ease-in-out {{ is_null($notification->read_at) ? 'bg-procon-light' : '' }}">
                                <div class="flex items-center gap-2">
                                    @if (is_null($notification->read_at))
                                        <span class="h-2 w-2 rounded-full bg-procon shrink-0"></span>
                                    @endif
                                    <span class="font-medium text-gray-800">{{ $notification->title }}</span>
                                </div>
                                <p class="text-gray-500 text-xs mt-0.5">{{ str($notification->message)->before("\n")->limit(60) }}</p>
                                <p class="text-gray-400 text-xs mt-0.5">{{ $notification->sent_at?->diffForHumans() }}</p>
                            </a>
                        @empty
                            <p class="px-4 py-3 text-sm text-gray-500">{{ __('No tienes notificaciones.') }}</p>
                        @endforelse
                    </x-slot>
                </x-dropdown>

                <x-dropdown align="right" width="48">
                    <x-slot name="trigger">
                        <button class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-gray-500 bg-white hover:text-gray-700 focus:outline-none transition ease-in-out duration-150">
                            <div>{{ Auth::user()->name }}</div>

                            <div class="ms-1">
                                <svg class="fill-current h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                </svg>
                            </div>
                        </button>
                    </x-slot>

                    <x-slot name="content">
                        <x-dropdown-link :href="route('profile.edit')">
                            {{ __('Profile') }}
                        </x-dropdown-link>

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
                <button @click="open = ! open" class="inline-flex items-center justify-center p-2 rounded-md text-gray-400 hover:text-gray-500 hover:bg-gray-100 focus:outline-none focus:bg-gray-100 focus:text-gray-500 transition duration-150 ease-in-out">
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
            <x-responsive-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')">
                {{ __('Dashboard') }}
            </x-responsive-nav-link>

            @canany(['quotations.view.all', 'quotations.view.search'])
                <x-responsive-nav-link :href="route('quotations.index')" :active="request()->routeIs('quotations.index')">
                    {{ __('Cotizaciones') }}
                </x-responsive-nav-link>
            @endcanany

            @can('quotations.assign')
                <x-responsive-nav-link :href="route('assignments.index')" :active="request()->routeIs('assignments.index')">
                    {{ __('Panel de asignación') }}
                </x-responsive-nav-link>
            @endcan

            @can('quotations.view.assigned')
                <x-responsive-nav-link :href="route('my-quotations.index')" :active="request()->routeIs('my-quotations.index')">
                    {{ __('Mis cotizaciones') }}
                </x-responsive-nav-link>
            @endcan

            @can('duplicates.view')
                <x-responsive-nav-link :href="route('duplicates.index')" :active="request()->routeIs('duplicates.index')">
                    {{ __('Duplicados') }}
                </x-responsive-nav-link>
            @endcan

            @can('reports.view')
                <x-responsive-nav-link :href="route('reports.index')" :active="request()->routeIs('reports.index')">
                    {{ __('Reportes') }}
                </x-responsive-nav-link>
            @endcan

            @canany(['facades.view', 'facades.view.all'])
                <x-responsive-nav-link :href="route('facades.index')" :active="request()->routeIs('facades.*')">
                    {{ __('Fachadas') }}
                </x-responsive-nav-link>
            @endcanany

            @can('users.manage')
                <x-responsive-nav-link :href="route('users.index')" :active="request()->routeIs('users.index')">
                    {{ __('Usuarios') }}
                </x-responsive-nav-link>
            @endcan
        </div>

        <!-- Responsive Notifications -->
        <div class="pt-4 pb-1 border-t border-gray-200">
            <div class="px-4 flex items-center justify-between">
                <span class="text-sm font-semibold text-gray-800">
                    {{ __('Notificaciones') }}
                    @if ($unreadNotificationsCount > 0)
                        <span class="ml-1 inline-flex items-center justify-center px-1.5 py-0.5 rounded-full text-xs font-semibold bg-red-500 text-white">
                            {{ $unreadNotificationsCount > 9 ? '9+' : $unreadNotificationsCount }}
                        </span>
                    @endif
                </span>
                @if ($unreadNotificationsCount > 0)
                    <form method="POST" action="{{ route('notifications.mark-all-read') }}">
                        @csrf
                        <button type="submit" class="text-xs text-procon hover:text-procon-dark">
                            {{ __('Marcar todas') }}
                        </button>
                    </form>
                @endif
            </div>

            <div class="mt-2 space-y-1">
                @forelse ($myNotifications as $notification)
                    <a href="{{ route('notifications.open', $notification) }}"
                       class="block px-4 py-2 text-start text-sm leading-5 hover:bg-gray-50 {{ is_null($notification->read_at) ? 'bg-procon-light' : '' }}">
                        <div class="flex items-center gap-2">
                            @if (is_null($notification->read_at))
                                <span class="h-2 w-2 rounded-full bg-procon shrink-0"></span>
                            @endif
                            <span class="font-medium text-gray-800">{{ $notification->title }}</span>
                        </div>
                        <p class="text-gray-500 text-xs mt-0.5">{{ str($notification->message)->before("\n")->limit(60) }}</p>
                    </a>
                @empty
                    <p class="px-4 py-2 text-sm text-gray-500">{{ __('No tienes notificaciones.') }}</p>
                @endforelse
            </div>
        </div>

        <!-- Responsive Settings Options -->
        <div class="pt-4 pb-1 border-t border-gray-200">
            <div class="px-4">
                <div class="font-medium text-base text-gray-800">{{ Auth::user()->name }}</div>
                <div class="font-medium text-sm text-gray-500">{{ Auth::user()->email }}</div>
            </div>

            <div class="mt-3 space-y-1">
                <x-responsive-nav-link :href="route('profile.edit')">
                    {{ __('Profile') }}
                </x-responsive-nav-link>

                <!-- Authentication -->
                <form method="POST" action="{{ route('logout') }}">
                    @csrf

                    <x-responsive-nav-link :href="route('logout')"
                            onclick="event.preventDefault();
                                        this.closest('form').submit();">
                        {{ __('Log Out') }}
                    </x-responsive-nav-link>
                </form>
            </div>
        </div>
    </div>
</nav>
