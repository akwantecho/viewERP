@extends('layouts.app')

@section('content')
<div class="max-w-3xl mx-auto px-4 py-8">
    <h2 class="text-2xl font-bold text-[#434141] mb-6">تعديل المستخدم</h2>

    @if ($errors->any())
        <div class="bg-red-100 text-red-700 p-3 rounded mb-4">
            <ul class="list-disc list-inside text-sm">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ route('users.update', $user->id) }}" method="POST" class="bg-white shadow rounded-lg p-6 space-y-5">
        @csrf
        @method('PUT')

        <div>
            <label class="block text-sm font-medium text-gray-700">الاسم</label>
            <input type="text" name="name" value="{{ old('name', $user->name) }}" required class="mt-1 w-full border border-gray-300 rounded px-4 py-2 focus:outline-none focus:ring-2 focus:ring-[#f5ce00]" />
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700">البريد الإلكتروني</label>
            <input type="email" name="email" value="{{ old('email', $user->email) }}" required class="mt-1 w-full border border-gray-300 rounded px-4 py-2 focus:outline-none focus:ring-2 focus:ring-[#f5ce00]" />
        </div>

        <div class="grid gap-4 md:grid-cols-2">
            <div>
                <label class="block text-sm font-medium text-gray-700">كلمة المرور الجديدة <span class="text-xs text-gray-400">(اختياري)</span></label>
                <input type="password" name="password" class="mt-1 w-full border border-gray-300 rounded px-4 py-2 focus:outline-none focus:ring-2 focus:ring-[#f5ce00]" />
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700">تأكيد كلمة المرور</label>
                <input type="password" name="password_confirmation" class="mt-1 w-full border border-gray-300 rounded px-4 py-2 focus:outline-none focus:ring-2 focus:ring-[#f5ce00]" />
            </div>
        </div>

        <div class="border rounded-lg">
            <div class="px-4 py-2 bg-gray-50 border-b text-sm font-semibold text-gray-700">الأدوار</div>
            <div class="p-4 grid grid-cols-1 md:grid-cols-2 gap-3 text-sm">
                @foreach($roles as $role)
                    <label class="inline-flex items-center gap-2 rounded border border-gray-200 px-3 py-2">
                        <input type="checkbox" name="roles[]" value="{{ $role->id }}" class="rounded" {{ in_array($role->id, old('roles', $selectedRoles ?? []), true) ? 'checked' : '' }}>
                        <span class="font-medium text-gray-800">{{ $role->name }}</span>
                    </label>
                @endforeach
            </div>
            <div class="px-4 pb-3 text-xs text-gray-500">قم بتحديث الأدوار لتعديل صلاحيات هذا المستخدم.</div>
        </div>

        <div class="text-right">
            <button type="submit" class="bg-[#f5ce00] hover:bg-yellow-400 text-[#434141] px-6 py-2 rounded font-semibold shadow">
                تحديث المستخدم
            </button>
        </div>
    </form>
</div>
@endsection
