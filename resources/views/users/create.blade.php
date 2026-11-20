<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Nuevo usuario') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8 space-y-6">

            @if ($errors->any())
                <div class="rounded-md bg-red-50 border border-red-200 text-red-800 text-sm p-4">
                    <ul class="list-disc list-inside">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <form method="POST" action="{{ route('users.store') }}" class="space-y-4">
                    @csrf

                    <div>
                        <label for="name" class="block text-sm font-medium text-gray-700">{{ __('Nombre') }}</label>
                        <input type="text" name="name" id="name" value="{{ old('name') }}" required
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-procon focus:ring-procon text-sm">
                    </div>

                    <div>
                        <label for="email" class="block text-sm font-medium text-gray-700">{{ __('Email') }}</label>
                        <input type="email" name="email" id="email" value="{{ old('email') }}" required
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-procon focus:ring-procon text-sm">
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label for="password" class="block text-sm font-medium text-gray-700">{{ __('Contraseña') }}</label>
                            <input type="password" name="password" id="password" required
                                   class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-procon focus:ring-procon text-sm">
                        </div>
                        <div>
                            <label for="password_confirmation" class="block text-sm font-medium text-gray-700">{{ __('Confirmar contraseña') }}</label>
                            <input type="password" name="password_confirmation" id="password_confirmation" required
                                   class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-procon focus:ring-procon text-sm">
                        </div>
                    </div>

                    <div>
                        <label for="role" class="block text-sm font-medium text-gray-700">{{ __('Rol') }}</label>
                        <select name="role" id="role" required
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-procon focus:ring-procon text-sm">
                            <option value="">{{ __('Selecciona un rol') }}</option>
                            @foreach ($roles as $role)
                                <option value="{{ $role }}" @selected(old('role') === $role)>{{ $roleLabels[$role] ?? ucfirst($role) }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="flex items-center">
                        <input type="checkbox" name="is_active" id="is_active" value="1" checked
                               class="rounded border-gray-300 text-procon shadow-sm focus:ring-procon">
                        <label for="is_active" class="ml-2 text-sm text-gray-700">{{ __('Usuario activo') }}</label>
                    </div>

                    <div class="flex items-center gap-3 pt-2">
                        <button type="submit" class="inline-flex items-center px-4 py-2 bg-procon border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-procon-dark">
                            {{ __('Crear usuario') }}
                        </button>
                        <a href="{{ route('users.index') }}" class="text-sm text-gray-600 hover:text-gray-900">
                            {{ __('Cancelar') }}
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
