<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Usuarios') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            @if (session('status'))
                <div class="rounded-md bg-green-50 border border-green-200 text-green-800 text-sm p-4">
                    {{ session('status') }}
                </div>
            @endif

            <div class="flex justify-end">
                <a href="{{ route('users.create') }}" class="inline-flex items-center px-4 py-2 bg-procon border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-procon-dark">
                    {{ __('Nuevo usuario') }}
                </a>
            </div>

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 text-sm">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left font-semibold text-gray-500 uppercase tracking-wider">{{ __('Nombre') }}</th>
                                <th class="px-4 py-3 text-left font-semibold text-gray-500 uppercase tracking-wider">{{ __('Email') }}</th>
                                <th class="px-4 py-3 text-left font-semibold text-gray-500 uppercase tracking-wider">{{ __('Rol') }}</th>
                                <th class="px-4 py-3 text-left font-semibold text-gray-500 uppercase tracking-wider">{{ __('Estatus') }}</th>
                                <th class="px-4 py-3 text-left font-semibold text-gray-500 uppercase tracking-wider">{{ __('Fecha de alta') }}</th>
                                <th class="px-4 py-3 text-left font-semibold text-gray-500 uppercase tracking-wider">{{ __('Acción') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            @foreach ($users as $user)
                                <tr class="hover:bg-gray-50">
                                    <td class="px-4 py-3 whitespace-nowrap font-medium text-gray-900">{{ $user->name }}</td>
                                    <td class="px-4 py-3 whitespace-nowrap text-gray-600">{{ $user->email }}</td>
                                    <td class="px-4 py-3 whitespace-nowrap text-gray-600">{{ $user->roles->pluck('name')->map(fn ($role) => $roleLabels[$role] ?? ucfirst($role))->implode(', ') ?: '—' }}</td>
                                    <td class="px-4 py-3 whitespace-nowrap">
                                        @if ($user->is_active)
                                            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-semibold bg-green-100 text-green-800">
                                                {{ __('Activo') }}
                                            </span>
                                        @else
                                            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-semibold bg-gray-100 text-gray-600">
                                                {{ __('Inactivo') }}
                                            </span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap text-gray-600">{{ $user->created_at->format('d/m/Y') }}</td>
                                    <td class="px-4 py-3 whitespace-nowrap">
                                        <a href="{{ route('users.edit', $user) }}"
                                           class="inline-flex items-center px-2.5 py-1.5 rounded-md border border-gray-300 text-xs font-medium text-gray-700 hover:bg-gray-50">
                                            {{ __('Editar') }}
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="p-4 border-t border-gray-200">
                    {{ $users->links() }}
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
