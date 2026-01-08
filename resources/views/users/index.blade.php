@extends('layouts.app')

@section('content')
<div class="max-w-7xl mx-auto px-4 py-6">
    <div class="flex justify-between items-center mb-6">
        <h2 class="text-2xl font-bold text-gray-700">{{ __('users.index.title') }}</h2>
        @can('users.create')
            <a href="{{ route('users.create') }}" class="bg-[#f5ce00] hover:bg-yellow-400 text-gray-800 px-4 py-2 rounded shadow">
                + {{ __('users.index.create_button') }}
            </a>
        @endcan
    </div>

    @if(session('success'))
        <div class="bg-green-100 text-green-800 p-3 rounded mb-4">
            {{ session('success') }}
        </div>
    @endif

    <div class="overflow-x-auto bg-white rounded shadow">
        <table class="min-w-full text-sm">
            <thead class="bg-[#434141] text-white text-left">
                <tr>
                    <th class="px-6 py-3">{{ __('users.index.table.id') }}</th>
                    <th class="px-6 py-3">{{ __('users.index.table.name') }}</th>
                    <th class="px-6 py-3">{{ __('users.index.table.email') }}</th>
                    <th class="px-6 py-3">{{ __('users.index.table.roles') }}</th>
                    <th class="px-6 py-3 text-right">{{ __('users.index.table.actions') }}</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                @forelse ($users as $index => $user)
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4">{{ $index + 1 }}</td>
                        <td class="px-6 py-4 font-medium">{{ $user->name }}</td>
                        <td class="px-6 py-4">{{ $user->email }}</td>
                        <td class="px-6 py-4">
                            <div class="flex flex-wrap gap-2">
                                @if($user->is_super)
                                    <span class="inline-flex items-center rounded-full bg-emerald-100 px-2 py-0.5 text-xs font-semibold text-emerald-700">{{ __('users.index.table.super_admin') }}</span>
                                @endif
                                @forelse($user->roles as $role)
                                    <span class="inline-flex items-center rounded-full bg-gray-100 px-2 py-0.5 text-xs text-gray-700">{{ $role->name }}</span>
                                @empty
                                    <span class="text-xs text-gray-400">{{ __('users.index.table.no_roles') }}</span>
                                @endforelse
                            </div>
                        </td>
                        <td class="px-6 py-4 text-right space-x-2">
                            @can('users.edit')
                                <a href="{{ route('users.edit', $user->id) }}" class="inline-block text-sm text-blue-600 hover:underline">{{ __('users.index.table.edit') }}</a>
                            @endcan

                            @can('users.delete')
                                <form action="{{ route('users.destroy', $user->id) }}" method="POST" class="inline-block" data-confirm="delete" data-confirm-title="{{ __('users.index.confirm.title') }}" data-confirm-message="{{ __('users.index.confirm.message') }}">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-sm text-red-600 hover:underline">{{ __('users.index.table.delete') }}</button>
                                </form>
                            @endcan
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="text-center py-6 text-gray-500">{{ __('users.index.empty') }}</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
