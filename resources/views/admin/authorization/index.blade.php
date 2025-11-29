@extends('layouts.app')

@php
    use Illuminate\Support\Str;
    $selectedPermissionIds = $selectedRole?->permissions->pluck('id')->all() ?? [];
@endphp

@section('content')
<div class="max-w-6xl mx-auto space-y-6">
    @if(session('success'))
        <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
            {{ session('success') }}
        </div>
    @endif

    @if($errors->any())
        <div class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
            <ul class="list-disc ps-5 space-y-1">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
            <h1 class="text-2xl font-semibold text-slate-900">{{ __('admin.authorization.title') }}</h1>
            <p class="text-sm text-slate-500">{{ __('admin.authorization.subtitle') }}</p>
        </div>
        <form method="POST" action="{{ route('admin.authorization.sync') }}">
            @csrf
            <button type="submit" class="inline-flex items-center gap-2 rounded-full bg-slate-900 px-4 py-2 text-sm font-semibold text-white shadow hover:bg-slate-800">
                {!! \App\Support\IconRegistry::svg('refresh', 'w-4 h-4') !!}
                {{ __('admin.authorization.sync') }}
            </button>
        </form>
    </div>

    <div class="grid gap-6 lg:grid-cols-[1.4fr,1fr]">
        <section class="space-y-4">
            <div class="flex items-center justify-between">
                <p class="text-sm font-semibold uppercase tracking-wide text-slate-500">{{ __('admin.authorization.roles_permissions.list_title') }}</p>
                <div class="flex gap-2">
                    <a href="{{ route('admin.authorization.index', ['mode' => 'create']) }}" class="rounded-full border border-slate-200 px-4 py-2 text-sm font-semibold text-slate-600 hover:bg-slate-50">
                        {{ __('admin.authorization.roles_permissions.new_role_button') }}
                    </a>
                    <a href="{{ route('admin.authorization.index') }}" class="rounded-full border border-slate-200 px-3 py-2 text-xs text-slate-500 hover:bg-slate-50">
                        {{ __('admin.authorization.roles_permissions.clear_selection') }}
                    </a>
                </div>
            </div>
            <div class="space-y-3">
                @forelse($roles as $role)
                    <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                        <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                            <div>
                                <p class="text-base font-semibold text-slate-900">{{ $role->name }}</p>
                                <p class="text-xs text-slate-500">{{ trans_choice('admin.authorization.roles_permissions.permission_count', $role->permissions->count(), ['count' => $role->permissions->count()]) }}</p>
                            </div>
                            <div class="flex flex-wrap gap-2">
                                <a href="{{ route('admin.authorization.index', ['role' => $role->id]) }}" class="inline-flex items-center gap-1 rounded-full border border-slate-200 px-3 py-1 text-xs font-semibold text-slate-700 hover:bg-slate-50">
                                    {!! \App\Support\IconRegistry::svg('pencil', 'w-3.5 h-3.5') !!}
                                    {{ __('buttons.edit') }}
                                </a>
                                @if($role->name !== 'super_admin')
                                    <form method="POST" action="{{ route('admin.authorization.roles.destroy', $role) }}" onsubmit="return confirm('{{ __('admin.authorization.roles_permissions.delete_confirm') }}');">
                                        @csrf
                                        @method('DELETE')
                                        <button class="inline-flex items-center gap-1 rounded-full border border-red-200 px-3 py-1 text-xs font-semibold text-red-600 hover:bg-red-50">
                                            {!! \App\Support\IconRegistry::svg('trash', 'w-3.5 h-3.5') !!}
                                            {{ __('buttons.delete') }}
                                        </button>
                                    </form>
                                @endif
                            </div>
                        </div>
                        @if($role->permissions->isNotEmpty())
                            <div class="mt-3 flex flex-wrap gap-2">
                                @foreach($role->permissions as $permission)
                                    <span class="inline-flex items-center rounded-full bg-slate-100 px-2 py-0.5 text-xs text-slate-600">{{ $permission->name }}</span>
                                @endforeach
                            </div>
                        @endif
                    </div>
                @empty
                    <p class="rounded-2xl border border-dashed border-slate-200 bg-white px-4 py-6 text-center text-sm text-slate-400">{{ __('admin.authorization.roles_permissions.no_roles') }}</p>
                @endforelse
            </div>
        </section>

        <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            @if($selectedRole)
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('admin.authorization.roles_permissions.edit_panel') }}</p>
                <h2 class="mt-1 text-xl font-semibold text-slate-900">{{ $selectedRole->name }}</h2>
                <form method="POST" action="{{ route('admin.authorization.roles.update', $selectedRole) }}" class="mt-4 space-y-5">
                    @csrf
                    @method('PUT')
                    <div>
                        <label class="text-sm font-semibold text-slate-700">{{ __('admin.authorization.roles_permissions.fields.name') }}</label>
                        <input type="text" name="name" value="{{ old('name', $selectedRole->name) }}" class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2 text-sm" @if($selectedRole->name === 'super_admin') disabled @endif required>
                    </div>
                    <div class="space-y-4 max-h-[450px] overflow-y-auto pe-1">
                        @foreach($permissionGroups as $group => $actions)
                            <div>
                                <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ Str::headline(str_replace('.', ' ', $group)) }}</p>
                                <div class="mt-2 flex flex-wrap gap-2">
                                    @foreach($actions as $action)
                                        @php
                                            $key = $group.'.'.$action;
                                            $permissionId = $permissionIdsByKey[$key] ?? null;
                                        @endphp
                                        @if($permissionId)
                                            <label class="inline-flex items-center gap-2 rounded-full border border-slate-200 px-3 py-1 text-xs text-slate-700">
                                                <input type="checkbox" name="permissions[]" value="{{ $permissionId }}" class="rounded" {{ in_array($permissionId, old('permissions', $selectedPermissionIds), true) ? 'checked' : '' }}>
                                                <span>{{ Str::headline($action) }}</span>
                                            </label>
                                        @endif
                                    @endforeach
                                </div>
                            </div>
                        @endforeach
                    </div>
                    <div class="flex flex-wrap gap-3">
                        <button class="inline-flex flex-1 items-center justify-center gap-2 rounded-full bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-800">
                            {!! \App\Support\IconRegistry::svg('save', 'w-4 h-4') !!}
                            {{ __('buttons.save') }}
                        </button>
                        <a href="{{ route('admin.authorization.index') }}" class="inline-flex items-center justify-center gap-2 rounded-full border border-slate-200 px-4 py-2 text-sm font-semibold text-slate-600 hover:bg-slate-50">{{ __('buttons.cancel') }}</a>
                    </div>
                </form>
            @else
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('admin.authorization.roles_permissions.create_panel') }}</p>
                <h2 class="mt-1 text-xl font-semibold text-slate-900">{{ __('admin.authorization.roles_permissions.create_title') }}</h2>
                <form method="POST" action="{{ route('admin.authorization.roles.store') }}" class="mt-4 space-y-5">
                    @csrf
                    <div>
                        <label class="text-sm font-semibold text-slate-700">{{ __('admin.authorization.roles_permissions.fields.name') }}</label>
                        <input type="text" name="name" value="{{ old('name') }}" class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2 text-sm" placeholder="{{ __('admin.authorization.roles_permissions.new_role_placeholder') }}" required>
                    </div>
                    <div class="space-y-4 max-h-[450px] overflow-y-auto pe-1">
                        @foreach($permissionGroups as $group => $actions)
                            <div>
                                <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ Str::headline(str_replace('.', ' ', $group)) }}</p>
                                <div class="mt-2 flex flex-wrap gap-2">
                                    @foreach($actions as $action)
                                        @php
                                            $key = $group.'.'.$action;
                                            $permissionId = $permissionIdsByKey[$key] ?? null;
                                        @endphp
                                        @if($permissionId)
                                            <label class="inline-flex items-center gap-2 rounded-full border border-slate-200 px-3 py-1 text-xs text-slate-700">
                                                <input type="checkbox" name="permissions[]" value="{{ $permissionId }}" class="rounded" {{ in_array($permissionId, old('permissions', []), true) ? 'checked' : '' }}>
                                                <span>{{ Str::headline($action) }}</span>
                                            </label>
                                        @endif
                                    @endforeach
                                </div>
                            </div>
                        @endforeach
                    </div>
                    <button class="inline-flex w-full items-center justify-center gap-2 rounded-full bg-emerald-600 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-500">
                        {!! \App\Support\IconRegistry::svg('plus', 'w-4 h-4') !!}
                        {{ __('admin.authorization.roles_permissions.add_role') }}
                    </button>
                </form>
            @endif
        </section>
    </div>
</div>
@endsection
