@extends('layouts.app')

@section('content')
<div class="max-w-7xl mx-auto px-4 py-8 space-y-8">
    <div class="flex items-center justify-between flex-wrap gap-4">
        <div>
            <h1 class="text-2xl font-bold text-slate-800">مركز النسخ الاحتياطي</h1>
            <p class="text-sm text-slate-500">واجهة موحّدة لنسخ تصريحات المشاريع احتياطياً، وجدولة التنفيذ، ومراجعة السجلات.</p>
        </div>
        <div class="text-sm text-slate-500">
            <span>اللغة الحالية:</span>
            <span class="font-semibold text-slate-700">{{ strtoupper(app()->getLocale()) }}</span>
        </div>
    </div>

    @foreach (['success' => 'green', 'warning' => 'amber', 'error' => 'red'] as $flash => $color)
        @if(session()->has($flash))
            <div class="rounded-lg bg-{{ $color }}-50 border border-{{ $color }}-200 px-4 py-3 text-sm text-{{ $color }}-900">
                {{ session($flash) }}
            </div>
        @endif
    @endforeach

    @php($frequencyLabels = ['daily' => 'يومي', 'weekly' => 'أسبوعي', 'monthly' => 'شهري'])
    @php($dayLabels = [
        'sunday' => 'الأحد',
        'monday' => 'الإثنين',
        'tuesday' => 'الثلاثاء',
        'wednesday' => 'الأربعاء',
        'thursday' => 'الخميس',
        'friday' => 'الجمعة',
        'saturday' => 'السبت',
    ])

    @if(session('statements_backup_summary'))
        @php($summary = session('statements_backup_summary'))
        <div class="rounded-lg bg-slate-50 border border-slate-200 px-4 py-3 text-sm text-slate-600 flex flex-wrap gap-4">
            <span class="font-semibold text-slate-800">ملخص آخر دفعة نسخ للتصريحات:</span>
            <span>عدد المشاريع: {{ $summary['projects'] }}</span>
            <span>ناجحة: {{ $summary['successful'] }}</span>
            <span>فاشلة: {{ $summary['failed'] }}</span>
            <span>معرّف الدفعة: {{ \Illuminate\Support\Str::limit($summary['batch_id'] ?? 'غير متاح', 8, '') }}</span>
            <span>مدة التنفيذ: {{ $summary['runtime'] ?? '—' }} ث</span>
        </div>
    @endif

    @if(session('last_backup_log_id'))
        <div class="rounded-lg bg-indigo-50 border border-indigo-200 px-4 py-3 text-xs text-indigo-900">
            رقم سجل المرجع: <span class="font-semibold">#{{ session('last_backup_log_id') }}</span>. راجع سجلات النسخ أدناه للتفاصيل الفنية.
        </div>
    @endif

    <div class="grid gap-6 md:grid-cols-2">
        <section class="bg-white shadow rounded-2xl p-6 border border-slate-100">
            <div class="flex items-center justify-between mb-4">
                <div>
                    <h2 class="text-lg font-semibold text-slate-800">نسخ التصريحات إلى S3</h2>
                    <p class="text-xs text-slate-500">توليد ملفات PDF لكل مشروع وتحميلها مباشرةً إلى S3.</p>
                </div>
                <form method="POST" action="{{ route('admin.backup-center.statements.backup') }}">
                    @csrf
                    <button type="submit" class="inline-flex items-center gap-2 rounded-lg bg-emerald-600 text-white px-4 py-2 text-sm font-semibold hover:bg-emerald-500">
                        {!! \App\Support\IconRegistry::svg('cloud-upload', 'w-4 h-4 text-white') !!}
                        <span>تشغيل النسخ الآن</span>
                    </button>
                </form>
            </div>
            <dl class="text-sm text-slate-600 space-y-2">
                <div class="flex justify-between">
                    <dt>عدد المشاريع المتاحة</dt>
                    <dd class="font-semibold text-slate-800">{{ $projectCount }}</dd>
                </div>
                <div class="flex justify-between">
                    <dt>آخر تشغيل</dt>
                    <dd class="font-semibold text-slate-800">
                        {{ optional(data_get($latestBatchSummary, 'ran_at'))->format('Y-m-d H:i') ?? 'غير متاح' }}
                    </dd>
                </div>
                @php($lastStatus = data_get($latestBatchSummary, 'status', 'unknown'))
                @php($lastStatusLabel = match ($lastStatus) {
                    'success' => 'ناجح',
                    'failed' => 'فاشل',
                    'unknown' => 'غير معروف',
                    default => $lastStatus,
                })
                <div class="flex justify-between">
                    <dt>حالة آخر تشغيل</dt>
                    <dd class="font-semibold {{ $lastStatus === 'failed' ? 'text-red-600' : 'text-emerald-600' }}">
                        {{ $lastStatusLabel }}
                    </dd>
                </div>
                <div class="flex justify-between">
                    <dt>المشاريع الناجحة</dt>
                    <dd>{{ data_get($latestBatchSummary, 'successful', '—') }}</dd>
                </div>
                <div class="flex justify-between">
                    <dt>المشاريع الفاشلة</dt>
                    <dd>{{ data_get($latestBatchSummary, 'failed', '—') }}</dd>
                </div>
                <div class="flex justify-between">
                    <dt>أقرب تشغيل مجدول</dt>
                    <dd>{{ optional($nextStatementsBackup)->format('Y-m-d H:i') ?? 'معطّل' }}</dd>
                </div>
            </dl>
        </section>

        <section class="bg-white shadow rounded-2xl border border-slate-100">
            <div class="p-6 border-b border-slate-100">
                <h2 class="text-lg font-semibold text-slate-800">نظرة على المجدول</h2>
                <p class="text-xs text-slate-500">إعدادات النسخ الاحتياطي التلقائي للتصريحات</p>
            </div>
            <div class="p-6 text-sm text-slate-600 space-y-4">
                <div>
                    <p class="text-xs uppercase tracking-wide text-slate-400">أتمتة النسخ</p>
                    <p class="font-semibold text-slate-800">{{ $settings->full_backup_enabled ? 'مفعّل' : 'معطّل' }}</p>
                    <p>التكرار: {{ $frequencyLabels[$settings->full_backup_frequency] ?? $settings->full_backup_frequency }} @ {{ $settings->full_backup_time }}</p>
                    <p>تفضيل اليوم: {{ $settings->full_backup_day_of_week ? ($dayLabels[$settings->full_backup_day_of_week] ?? $settings->full_backup_day_of_week) : '—' }} / {{ $settings->full_backup_day_of_month ?? '—' }}</p>
                </div>
                <div>
                    <p class="text-xs uppercase tracking-wide text-slate-400">آخر دفعة</p>
                    <p class="font-semibold text-slate-800">دفعة {{ \Illuminate\Support\Str::limit(data_get($latestBatchSummary, 'batch_id', '—'), 8, '') }}</p>
                    <p>{{ data_get($latestBatchSummary, 'successful', 0) }} ناجحة / {{ data_get($latestBatchSummary, 'failed', 0) }} فاشلة</p>
                </div>
            </div>
        </section>
    </div>

    <section class="bg-white shadow rounded-2xl border border-slate-100">
        <div class="p-6 border-b border-slate-100">
            <h2 class="text-lg font-semibold text-slate-800">إعدادات نسخ التصريحات</h2>
            <p class="text-xs text-slate-500">اضبط النسخ التلقائي لتقارير المشاريع إلى S3</p>
        </div>
        <form method="POST" action="{{ route('admin.backup-center.settings.update') }}" class="p-6 space-y-4">
            @csrf
            @method('PUT')
            <div class="grid md:grid-cols-2 gap-4">
                <label class="space-y-1 text-sm text-slate-600">
                    <span>تفعيل النسخ التلقائي للتصريحات</span>
                    <select name="full_backup_enabled" class="form-select w-full rounded-lg border-slate-300">
                        <option value="1" @selected($settings->full_backup_enabled)>مفعّل</option>
                        <option value="0" @selected(!$settings->full_backup_enabled)>معطّل</option>
                    </select>
                </label>
                <label class="space-y-1 text-sm text-slate-600">
                    <span>تكرار النسخ</span>
                    <select name="full_backup_frequency" class="form-select w-full rounded-lg border-slate-300">
                        @foreach($frequencyLabels as $value => $label)
                            <option value="{{ $value }}" @selected($settings->full_backup_frequency === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </label>
                <label class="space-y-1 text-sm text-slate-600">
                    <span>وقت التنفيذ (24 ساعة)</span>
                    <input type="time" name="full_backup_time" value="{{ $settings->full_backup_time }}" class="form-input w-full rounded-lg border-slate-300">
                </label>
                <label class="space-y-1 text-sm text-slate-600">
                    <span>اليوم المفضّل في الأسبوع</span>
                    <select name="full_backup_day_of_week" class="form-select w-full rounded-lg border-slate-300">
                        <option value="">أي يوم</option>
                        @foreach($dayLabels as $value => $label)
                            <option value="{{ $value }}" @selected($settings->full_backup_day_of_week === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </label>
                <label class="space-y-1 text-sm text-slate-600">
                    <span>اليوم المفضّل في الشهر</span>
                    <input type="number" min="1" max="28" name="full_backup_day_of_month" value="{{ $settings->full_backup_day_of_month }}" class="form-input w-full rounded-lg border-slate-300">
                </label>
            </div>
            <div>
                <button type="submit" class="inline-flex items-center gap-2 rounded-lg bg-emerald-600 text-white px-4 py-2 text-sm font-semibold hover:bg-emerald-500">
                    {!! \App\Support\IconRegistry::svg('save', 'w-4 h-4 text-white') !!}
                    <span>حفظ الإعدادات</span>
                </button>
            </div>
        </form>
    </section>

    <section class="bg-white shadow rounded-2xl border border-slate-100">
        <div class="p-6 border-b border-slate-100 flex items-center justify-between">
            <div>
                <h2 class="text-lg font-semibold text-slate-800">سجلات النسخ والمزامنة</h2>
                <p class="text-xs text-slate-500">متابعة نسخ التصريحات (يدوي/مجدول)</p>
            </div>
            <div class="flex gap-2 text-xs font-semibold">
                <a href="{{ route('admin.backup-center.logs.backups') }}" class="px-3 py-1.5 rounded-full {{ $activeTab === 'backup_logs' ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-600' }}">سجلات النسخ</a>
                <a href="{{ route('admin.backup-center.logs.sync') }}" class="px-3 py-1.5 rounded-full {{ $activeTab === 'sync_logs' ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-600' }}">سجلات المزامنة</a>
            </div>
        </div>
        <div class="p-6">
            @if($activeTab === 'sync_logs')
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-100 text-sm">
                        <thead class="bg-slate-50 text-slate-500">
                            <tr>
                                <th class="px-4 py-2 text-left">الوقت</th>
                                <th class="px-4 py-2 text-left">المشروع</th>
                                <th class="px-4 py-2 text-left">آلية التشغيل</th>
                                <th class="px-4 py-2 text-left">تم التنفيذ بواسطة</th>
                                <th class="px-4 py-2 text-left">مدة التنفيذ (ث)</th>
                                <th class="px-4 py-2 text-left">الحالة</th>
                                <th class="px-4 py-2 text-left">المسار على السحابة</th>
                                <th class="px-4 py-2 text-left">الرسالة</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 text-slate-700">
                            @forelse($syncLogs as $log)
                                @php($triggerLabel = match ($log->trigger) {
                                    'scheduler' => 'مجدول',
                                    'manual' => 'يدوي',
                                    default => $log->trigger,
                                })
                                @php($statusLabel = $log->status === 'failed' ? 'فشل' : ($log->status === 'success' ? 'نجاح' : $log->status))
                                <tr>
                                    <td class="px-4 py-3">{{ $log->created_at->format('Y-m-d H:i') }}</td>
                                    <td class="px-4 py-3">{{ optional($log->project)->name ?? ($log->project_id ? '#' . $log->project_id : '—') }}</td>
                                    <td class="px-4 py-3">{{ $triggerLabel }}</td>
                                    <td class="px-4 py-3">{{ $log->triggered_by ?? 'النظام' }}</td>
                                    <td class="px-4 py-3">{{ $log->runtime_duration ?? '—' }}</td>
                                    <td class="px-4 py-3">
                                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-semibold {{ $log->status === 'failed' ? 'bg-red-100 text-red-700' : 'bg-emerald-100 text-emerald-700' }}">
                                            {{ $statusLabel }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-xs font-mono">{{ \Illuminate\Support\Str::limit($log->remote_path, 40) }}</td>
                                    <td class="px-4 py-3">{{ $log->message }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="px-4 py-6 text-center text-slate-500">لا توجد سجلات مزامنة.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="mt-4">
                    {{ $syncLogs->withQueryString()->links() }}
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-100 text-sm">
                        <thead class="bg-slate-50 text-slate-500">
                            <tr>
                                <th class="px-4 py-2 text-left">الوقت</th>
                                <th class="px-4 py-2 text-left">المشروع</th>
                                <th class="px-4 py-2 text-left">القرص</th>
                                <th class="px-4 py-2 text-left">تم التنفيذ بواسطة</th>
                                <th class="px-4 py-2 text-left">مدة التنفيذ (ث)</th>
                                <th class="px-4 py-2 text-left">بصمة الملف</th>
                                <th class="px-4 py-2 text-left">الحالة</th>
                                <th class="px-4 py-2 text-left">الرسالة</th>
                                <th class="px-4 py-2 text-left">الإجراءات</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 text-slate-700">
                            @forelse($backupLogs as $log)
                                @php($statusLabel = $log->status === 'failed' ? 'فشل' : ($log->status === 'success' ? 'نجاح' : $log->status))
                                <tr>
                                    <td class="px-4 py-3">{{ $log->created_at->format('Y-m-d H:i') }}</td>
                                    <td class="px-4 py-3">{{ optional($log->project)->name ?? 'دفعة التصريحات' }}</td>
                                    <td class="px-4 py-3">{{ strtoupper($log->disk) }}</td>
                                    <td class="px-4 py-3">{{ $log->triggered_by ?? 'النظام' }}</td>
                                    <td class="px-4 py-3">{{ $log->runtime_duration ?? '—' }}</td>
                                    <td class="px-4 py-3 text-xs font-mono">{{ $log->file_hash ? \Illuminate\Support\Str::limit($log->file_hash, 12) : '—' }}</td>
                                    <td class="px-4 py-3">
                                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-semibold {{ $log->status === 'failed' ? 'bg-red-100 text-red-700' : 'bg-emerald-100 text-emerald-700' }}">
                                            {{ $statusLabel }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3">{{ \Illuminate\Support\Str::limit($log->message, 80) }}</td>
                                    <td class="px-4 py-3">
                                        <div class="flex gap-2">
                                            @if($log->cloud_url)
                                                <a href="{{ $log->cloud_url }}" target="_blank" class="px-2 py-1 text-xs font-semibold rounded bg-emerald-50 text-emerald-700 hover:bg-emerald-100">رابط السحابة</a>
                                            @endif
                                            <form method="GET" action="{{ route('admin.backup-center.download') }}">
                                                <input type="hidden" name="disk" value="{{ $log->disk }}">
                                                <input type="hidden" name="path" value="{{ $log->file_name }}">
                                                <button type="submit" class="px-2 py-1 text-xs font-semibold rounded bg-slate-100 text-slate-600 hover:bg-slate-200">تحميل</button>
                                            </form>
                                            <form method="POST" action="{{ route('admin.backup-center.delete') }}" onsubmit="return confirm('هل تريد حذف ملف النسخة الاحتياطية؟');">
                                                @csrf
                                                @method('DELETE')
                                                <input type="hidden" name="disk" value="{{ $log->disk }}">
                                                <input type="hidden" name="path" value="{{ $log->file_name }}">
                                                <button type="submit" class="px-2 py-1 text-xs font-semibold rounded bg-red-50 text-red-600 hover:bg-red-100">حذف</button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="9" class="px-4 py-6 text-center text-slate-500">لا توجد سجلات نسخ احتياطي.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="mt-4">
                    {{ $backupLogs->withQueryString()->links() }}
                </div>
            @endif
        </div>
    </section>
</div>
@endsection
