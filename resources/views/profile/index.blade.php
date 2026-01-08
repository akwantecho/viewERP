@extends('layouts.app')

@section('content')
<div class="max-w-7xl mx-auto p-6 space-y-8">

    <h1 class="text-2xl font-bold text-[#1f2937]">{{ __('profile.title') }}</h1>

    @if(session('success'))
        <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
            {{ session('success') }}
        </div>
    @endif

    <div class="grid gap-6 lg:grid-cols-2">
        <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm space-y-4">
            <h2 class="text-lg font-semibold text-gray-900">{{ __('profile.update_name.title') }}</h2>
            <form method="POST" action="{{ route('profile.updateName') }}" class="space-y-4">
                @csrf
                @method('PATCH')
                <div>
                    <label class="block text-sm font-medium text-gray-600">{{ __('profile.update_name.full_name') }}</label>
                    <input type="text" name="name" value="{{ old('name', auth()->user()->name) }}"
                           class="mt-1 w-full rounded-lg border border-gray-200 px-3 py-2 text-sm">
                    @error('name')
                        <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                    @enderror
                </div>
                <div class="flex justify-end">
                    <button class="rounded-lg bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-700">
                        {{ __('profile.update_name.save') }}
                    </button>
                </div>
            </form>
        </div>

        <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm space-y-4">
            <h2 class="text-lg font-semibold text-gray-900">{{ __('profile.update_password.title') }}</h2>
            <form method="POST" action="{{ route('profile.updatePassword') }}" class="space-y-4">
                @csrf
                @method('PATCH')
                <div>
                    <label class="block text-sm font-medium text-gray-600">{{ __('profile.update_password.current_password') }}</label>
                    <input type="password" name="current_password"
                           class="mt-1 w-full rounded-lg border border-gray-200 px-3 py-2 text-sm" required>
                    @error('current_password')
                        <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-600">{{ __('profile.update_password.new_password') }}</label>
                    <input type="password" name="password"
                           class="mt-1 w-full rounded-lg border border-gray-200 px-3 py-2 text-sm" required>
                    @error('password')
                        <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-600">{{ __('profile.update_password.confirm_password') }}</label>
                    <input type="password" name="password_confirmation"
                           class="mt-1 w-full rounded-lg border border-gray-200 px-3 py-2 text-sm" required>
                </div>
                <div class="flex justify-end">
                    <button class="rounded-lg bg-amber-600 px-4 py-2 text-sm font-semibold text-white hover:bg-amber-500">
                        {{ __('profile.update_password.update_button') }}
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
        <!-- Users Management -->
        <a href="{{ route('users.index') }}" class="block rounded-xl shadow border bg-white hover:bg-gray-50">
            <div class="p-6 space-y-2">
                <div class="text-xl font-semibold text-[#434141]">{{ __('profile.quick_links.users.title') }}</div>
                <p class="text-sm text-gray-600">{{ __('profile.quick_links.users.description') }}</p>
            </div>
        </a>

        <!-- Files Management / Sync Logs -->
        <a href="{{ route('profile.files') }}" class="block rounded-xl shadow border bg-white hover:bg-gray-50">
            <div class="p-6 space-y-2">
                <div class="text-xl font-semibold text-[#434141]">{{ __('profile.quick_links.files.title') }}</div>
                <p class="text-sm text-gray-600">{{ __('profile.quick_links.files.description') }}</p>
            </div>
        </a>

        <!-- Total Statement All -->
        <a href="{{ route('profile.totalStatementAll') }}" class="block rounded-xl shadow border bg-white hover:bg-gray-50">
            <div class="p-6 space-y-2">
                <div class="text-xl font-semibold text-[#434141]">{{ __('profile.quick_links.total_statement.title') }}</div>
                <p class="text-sm text-gray-600">{{ __('profile.quick_links.total_statement.description') }}</p>
            </div>
        </a>

        @if(auth()->user()?->is_super)
        <!-- Activities (Super Admin) -->
        <a href="{{ route('profile.activities') }}" class="block rounded-xl shadow border bg-white hover:bg-gray-50">
            <div class="p-6 space-y-2">
                <div class="text-xl font-semibold text-[#434141]">{{ __('profile.quick_links.activities.title') }}</div>
                <p class="text-sm text-gray-600">{{ __('profile.quick_links.activities.description') }}</p>
            </div>
        </a>
        @endif
    </div>

</div>
@endsection
