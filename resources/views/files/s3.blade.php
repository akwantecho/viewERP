@extends('layouts.app')

@section('content')
<div class="max-w-6xl mx-auto px-6 py-10 space-y-8">
    <div class="flex flex-col gap-2 md:flex-row md:items-center md:justify-between">
        <div>
            <p class="text-xs font-semibold uppercase tracking-[0.35em] text-slate-400">التخزين</p>
            <h1 class="text-2xl font-semibold text-slate-900">مدير S3</h1>
            <p class="text-sm text-slate-500">المسار الحالي: <span class="font-mono">/{{ $currentDir ?: 'root' }}</span></p>
        </div>
        <div class="flex flex-wrap gap-2 text-sm">
            @if(!is_null($parentDir))
                <a href="{{ route('storage.files.index', ['dir' => $parentDir ?: null]) }}" class="rounded-lg border border-slate-200 px-3 py-2 text-slate-600 hover:bg-slate-50">⬆️ الرجوع مستوى واحد</a>
            @endif
            <a href="{{ route('storage.files.index') }}" class="rounded-lg border border-slate-200 px-3 py-2 text-slate-600 hover:bg-slate-50">الجذر</a>
        </div>
    </div>

    @if (!$diskReady)
        <div class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-600">
            قرص S3 غير مهيأ. حدّث بيانات الاعتماد في ملف <code>.env</code> أولاً.
        </div>
    @else

    @if ($error)
        <div class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
            {{ $error }}
        </div>
    @endif

    @if (session('success'))
        <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
            {{ session('success') }}
        </div>
    @elseif (session('error'))
        <div class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
            {{ session('error') }}
        </div>
    @endif

    <div class="grid gap-6 md:grid-cols-2">
        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <h2 class="text-lg font-semibold text-slate-900">رفع / استبدال</h2>
            <form class="mt-4 space-y-4" method="POST" action="{{ route('storage.files.store') }}" enctype="multipart/form-data">
                @csrf
                <input type="hidden" name="dir" value="{{ $currentDir }}">
                <div>
                    <label class="text-sm font-medium text-slate-600">المجلد المستهدف</label>
                    <input type="text" value="/{{ $currentDir ?: '' }}" disabled class="mt-1 w-full rounded-lg border border-slate-200 px-3 py-2 text-sm text-slate-700 bg-slate-50">
                </div>
                <div>
                    <label class="text-sm font-medium text-slate-600">اختر ملفاً</label>
                    <input type="file" name="file" required class="mt-1 w-full rounded-lg border border-slate-200 px-3 py-2 text-sm">
                </div>
                <div>
                    <label class="text-sm font-medium text-slate-600">اسم مخصص (اختياري)</label>
                    <input type="text" name="filename" class="mt-1 w-full rounded-lg border border-slate-200 px-3 py-2 text-sm" placeholder="example.pdf">
                </div>
                <label class="inline-flex items-center gap-2 text-sm text-slate-600">
                    <input type="checkbox" name="replace" value="1" class="rounded border-slate-300">
                    استبدال الملف الحالي إذا توافق الاسم
                </label>
                <div class="flex justify-end">
                    <button class="inline-flex items-center gap-2 rounded-lg bg-slate-900 px-4 py-2 text-sm font-semibold text-white shadow hover:bg-slate-800">
                        ⬆️ رفع الملف
                    </button>
                </div>
            </form>
        </div>

        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <h2 class="text-lg font-semibold text-slate-900">مسار التنقل</h2>
            <div class="mt-3 flex flex-wrap items-center gap-2 text-sm">
                <a href="{{ route('storage.files.index') }}" class="rounded-full bg-slate-100 px-3 py-1 font-semibold text-slate-700">/</a>
                @foreach($breadcrumbs as $crumb)
                    <span class="text-slate-400">›</span>
                    <a href="{{ route('storage.files.index', ['dir' => $crumb['path']]) }}" class="rounded-full bg-slate-100 px-3 py-1 font-semibold text-slate-700">{{ $crumb['label'] }}</a>
                @endforeach
            </div>
        </div>
    </div>

    <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
        <div class="flex items-center justify-between">
            <h2 class="text-lg font-semibold text-slate-900">المجلدات</h2>
            <span class="text-sm text-slate-500">تم العثور على {{ $directories->count() }}</span>
        </div>
        <div class="mt-4 grid gap-3 md:grid-cols-2">
            @forelse($directories as $directory)
                <a href="{{ route('storage.files.index', ['dir' => $directory['path']]) }}" class="flex items-center justify-between rounded-xl border border-slate-200 px-4 py-3 text-sm text-slate-700 hover:border-slate-300">
                    <div>
                        <p class="font-semibold">{{ $directory['name'] ?: '/' }}</p>
                        <p class="text-xs text-slate-400">/{{ $directory['path'] }}</p>
                    </div>
                    <span class="text-slate-400">→</span>
                </a>
            @empty
                <p class="text-sm text-slate-500">لا يوجد مجلدات فرعية هنا.</p>
            @endforelse
        </div>
    </div>

    <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
        <div class="flex items-center justify-between">
            <h2 class="text-lg font-semibold text-slate-900">الملفات</h2>
            <span class="text-sm text-slate-500">{{ $files->count() }} ملف</span>
        </div>
        <div class="overflow-x-auto mt-4">
            <table class="w-full min-w-[640px] text-sm">
                <thead class="bg-slate-50 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                    <tr>
                        <th class="px-3 py-2">الاسم</th>
                        <th class="px-3 py-2">الحجم</th>
                        <th class="px-3 py-2">آخر تحديث</th>
                        <th class="px-3 py-2">الإجراءات</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($files as $file)
                        <tr>
                            <td class="px-3 py-2 font-mono text-slate-700">{{ $file['name'] }}</td>
                            <td class="px-3 py-2">{{ $file['size'] ? number_format($file['size'] / 1024, 1) . ' KB' : '—' }}</td>
                            <td class="px-3 py-2">{{ $file['lastModified'] ? \Carbon\Carbon::createFromTimestamp($file['lastModified'])->format('Y-m-d H:i') : '—' }}</td>
                            <td class="px-3 py-2">
                                <div class="flex flex-wrap gap-2">
                                    @if($file['url'])
                                        <a href="{{ $file['url'] }}" target="_blank" class="rounded border border-blue-200 px-2 py-1 text-xs font-semibold text-blue-600 hover:bg-blue-50">فتح</a>
                                    @endif
                                    <form method="POST" action="{{ route('storage.files.destroy') }}" onsubmit="return confirm('حذف {{ $file['name'] }}؟');">
                                        @csrf
                                        @method('DELETE')
                                        <input type="hidden" name="path" value="{{ $file['path'] }}">
                                        <input type="hidden" name="dir" value="{{ $currentDir }}">
                                        <button type="submit" class="rounded border border-red-200 px-2 py-1 text-xs font-semibold text-red-600 hover:bg-red-50">حذف</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-3 py-6 text-center text-sm text-slate-500">لا توجد ملفات في هذا المجلد.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @endif
</div>
@endsection
