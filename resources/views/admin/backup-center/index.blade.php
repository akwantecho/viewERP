@extends('layouts.app')

@section('content')
<div class="max-w-7xl mx-auto px-4 py-8 space-y-8">
    <div class="flex items-center justify-between flex-wrap gap-4">
        <div>
            <h1 class="text-2xl font-bold text-slate-800">{{ __('admin.backup_center.title') }}</h1>
            <p class="text-sm text-slate-500">{{ __('admin.backup_center.subtitle') }}</p>
        </div>
        <div class="text-sm text-slate-500">
            <span>{{ __('admin.backup_center.current_language') }}</span>
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

    @php($frequencyLabels = [
        'daily' => __('admin.backup_center.frequency.daily'),
        'weekly' => __('admin.backup_center.frequency.weekly'),
        'monthly' => __('admin.backup_center.frequency.monthly')
    ])
    @php($dayLabels = [
        'sunday' => __('admin.backup_center.days.sunday'),
        'monday' => __('admin.backup_center.days.monday'),
        'tuesday' => __('admin.backup_center.days.tuesday'),
        'wednesday' => __('admin.backup_center.days.wednesday'),
        'thursday' => __('admin.backup_center.days.thursday'),
        'friday' => __('admin.backup_center.days.friday'),
        'saturday' => __('admin.backup_center.days.saturday'),
    ])

    @if(session('statements_backup_summary'))
        @php($summary = session('statements_backup_summary'))
        <div class="rounded-lg bg-slate-50 border border-slate-200 px-4 py-3 text-sm text-slate-600 flex flex-wrap gap-4">
            <span class="font-semibold text-slate-800">{{ __('admin.backup_center.last_batch_summary') }}</span>
            <span>{{ __('admin.backup_center.projects_count') }}: {{ $summary['projects'] }}</span>
            <span>{{ __('admin.backup_center.successful') }}: {{ $summary['successful'] }}</span>
            <span>{{ __('admin.backup_center.failed') }}: {{ $summary['failed'] }}</span>
            <span>{{ __('admin.backup_center.batch_id') }}: {{ \Illuminate\Support\Str::limit($summary['batch_id'] ?? __('admin.backup_center.statements_backup.not_available'), 8, '') }}</span>
            <span>{{ __('admin.backup_center.runtime') }}: {{ $summary['runtime'] ?? '—' }} ث</span>
        </div>
    @endif

    @if(session('last_backup_log_id'))
        <div class="rounded-lg bg-indigo-50 border border-indigo-200 px-4 py-3 text-xs text-indigo-900">
            {{ __('admin.backup_center.reference_log') }} <span class="font-semibold">#{{ session('last_backup_log_id') }}</span>. {{ __('admin.backup_center.review_logs') }}
        </div>
    @endif

    <div class="grid gap-6 md:grid-cols-2">
        <section class="bg-white shadow rounded-2xl p-6 border border-slate-100">
            <div class="flex items-center justify-between mb-4">
                <div>
                    <h2 class="text-lg font-semibold text-slate-800">{{ __('admin.backup_center.statements_backup.title') }}</h2>
                    <p class="text-xs text-slate-500">{{ __('admin.backup_center.statements_backup.subtitle') }}</p>
                </div>
                <form method="POST" action="{{ route('admin.backup-center.statements.backup') }}">
                    @csrf
                    <button type="submit" class="inline-flex items-center gap-2 rounded-lg bg-emerald-600 text-white px-4 py-2 text-sm font-semibold hover:bg-emerald-500">
                        {!! \App\Support\IconRegistry::svg('cloud-upload', 'w-4 h-4 text-white') !!}
                        <span>{{ __('admin.backup_center.statements_backup.run_now') }}</span>
                    </button>
                </form>
            </div>
            <dl class="text-sm text-slate-600 space-y-2">
                <div class="flex justify-between">
                    <dt>{{ __('admin.backup_center.statements_backup.available_projects') }}</dt>
                    <dd class="font-semibold text-slate-800">{{ $projectCount }}</dd>
                </div>
                <div class="flex justify-between">
                    <dt>{{ __('admin.backup_center.statements_backup.last_run') }}</dt>
                    <dd class="font-semibold text-slate-800">
                        {{ optional(data_get($latestBatchSummary, 'ran_at'))->format('Y-m-d H:i') ?? __('admin.backup_center.statements_backup.not_available') }}
                    </dd>
                </div>
                @php($lastStatus = data_get($latestBatchSummary, 'status', 'unknown'))
                @php($lastStatusLabel = __('admin.backup_center.status.' . $lastStatus))
                <div class="flex justify-between">
                    <dt>{{ __('admin.backup_center.statements_backup.last_run_status') }}</dt>
                    <dd class="font-semibold {{ $lastStatus === 'failed' ? 'text-red-600' : 'text-emerald-600' }}">
                        {{ $lastStatusLabel }}
                    </dd>
                </div>
                <div class="flex justify-between">
                    <dt>{{ __('admin.backup_center.statements_backup.successful_projects') }}</dt>
                    <dd>{{ data_get($latestBatchSummary, 'successful', '—') }}</dd>
                </div>
                <div class="flex justify-between">
                    <dt>{{ __('admin.backup_center.statements_backup.failed_projects') }}</dt>
                    <dd>{{ data_get($latestBatchSummary, 'failed', '—') }}</dd>
                </div>
                <div class="flex justify-between">
                    <dt>{{ __('admin.backup_center.statements_backup.next_scheduled_run') }}</dt>
                    <dd>{{ optional($nextStatementsBackup)->format('Y-m-d H:i') ?? __('admin.backup_center.statements_backup.disabled') }}</dd>
                </div>
            </dl>
        </section>

        <section class="bg-white shadow rounded-2xl border border-slate-100">
            <div class="p-6 border-b border-slate-100">
                <h2 class="text-lg font-semibold text-slate-800">{{ __('admin.backup_center.scheduler_overview.title') }}</h2>
                <p class="text-xs text-slate-500">{{ __('admin.backup_center.scheduler_overview.subtitle') }}</p>
            </div>
            <div class="p-6 text-sm text-slate-600 space-y-4">
                <div>
                    <p class="text-xs uppercase tracking-wide text-slate-400">{{ __('admin.backup_center.scheduler_overview.backup_automation') }}</p>
                    <p class="font-semibold text-slate-800">{{ $settings->full_backup_enabled ? __('admin.backup_center.scheduler_overview.enabled') : __('admin.backup_center.scheduler_overview.disabled') }}</p>
                    <p>{{ __('admin.backup_center.scheduler_overview.frequency') }}: {{ $frequencyLabels[$settings->full_backup_frequency] ?? $settings->full_backup_frequency }} @ {{ $settings->full_backup_time }}</p>
                    <p>{{ __('admin.backup_center.scheduler_overview.day_preference') }}: {{ $settings->full_backup_day_of_week ? ($dayLabels[$settings->full_backup_day_of_week] ?? $settings->full_backup_day_of_week) : '—' }} / {{ $settings->full_backup_day_of_month ?? '—' }}</p>
                </div>
                <div>
                    <p class="text-xs uppercase tracking-wide text-slate-400">{{ __('admin.backup_center.scheduler_overview.last_batch') }}</p>
                    <p class="font-semibold text-slate-800">{{ __('admin.backup_center.scheduler_overview.batch') }} {{ \Illuminate\Support\Str::limit(data_get($latestBatchSummary, 'batch_id', '—'), 8, '') }}</p>
                    <p>{{ data_get($latestBatchSummary, 'successful', 0) }} {{ __('admin.backup_center.successful') }} / {{ data_get($latestBatchSummary, 'failed', 0) }} {{ __('admin.backup_center.failed') }}</p>
                </div>
            </div>
        </section>
    </div>

    <section class="bg-white shadow rounded-2xl border border-slate-100">
        <div class="p-6 border-b border-slate-100">
            <h2 class="text-lg font-semibold text-slate-800">{{ __('admin.backup_center.settings.title') }}</h2>
            <p class="text-xs text-slate-500">{{ __('admin.backup_center.settings.subtitle') }}</p>
        </div>
        <form method="POST" action="{{ route('admin.backup-center.settings.update') }}" class="p-6 space-y-4">
            @csrf
            @method('PUT')
            <div class="grid md:grid-cols-2 gap-4">
                <label class="space-y-1 text-sm text-slate-600">
                    <span>{{ __('admin.backup_center.settings.enable_auto_backup') }}</span>
                    <select name="full_backup_enabled" class="form-select w-full rounded-lg border-slate-300">
                        <option value="1" @selected($settings->full_backup_enabled)>{{ __('admin.backup_center.scheduler_overview.enabled') }}</option>
                        <option value="0" @selected(!$settings->full_backup_enabled)>{{ __('admin.backup_center.scheduler_overview.disabled') }}</option>
                    </select>
                </label>
                <label class="space-y-1 text-sm text-slate-600">
                    <span>{{ __('admin.backup_center.settings.backup_frequency') }}</span>
                    <select name="full_backup_frequency" class="form-select w-full rounded-lg border-slate-300">
                        @foreach($frequencyLabels as $value => $label)
                            <option value="{{ $value }}" @selected($settings->full_backup_frequency === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </label>
                <label class="space-y-1 text-sm text-slate-600">
                    <span>{{ __('admin.backup_center.settings.execution_time') }}</span>
                    <input type="time" name="full_backup_time" value="{{ $settings->full_backup_time }}" class="form-input w-full rounded-lg border-slate-300">
                </label>
                <label class="space-y-1 text-sm text-slate-600">
                    <span>{{ __('admin.backup_center.settings.preferred_day_week') }}</span>
                    <select name="full_backup_day_of_week" class="form-select w-full rounded-lg border-slate-300">
                        <option value="">{{ __('admin.backup_center.settings.any_day') }}</option>
                        @foreach($dayLabels as $value => $label)
                            <option value="{{ $value }}" @selected($settings->full_backup_day_of_week === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </label>
                <label class="space-y-1 text-sm text-slate-600">
                    <span>{{ __('admin.backup_center.settings.preferred_day_month') }}</span>
                    <input type="number" min="1" max="28" name="full_backup_day_of_month" value="{{ $settings->full_backup_day_of_month }}" class="form-input w-full rounded-lg border-slate-300">
                </label>
            </div>
            <div>
                <button type="submit" class="inline-flex items-center gap-2 rounded-lg bg-emerald-600 text-white px-4 py-2 text-sm font-semibold hover:bg-emerald-500">
                    {!! \App\Support\IconRegistry::svg('save', 'w-4 h-4 text-white') !!}
                    <span>{{ __('admin.backup_center.settings.save_settings') }}</span>
                </button>
            </div>
        </form>
    </section>

    <section class="bg-white shadow rounded-2xl border border-slate-100">
        <div class="p-6 border-b border-slate-100 flex items-center justify-between">
            <div>
                <h2 class="text-lg font-semibold text-slate-800">{{ __('admin.backup_center.logs.title') }}</h2>
                <p class="text-xs text-slate-500">{{ __('admin.backup_center.logs.subtitle') }}</p>
            </div>
            <div class="flex gap-2 text-xs font-semibold">
                <a href="{{ route('admin.backup-center.logs.backups') }}" class="px-3 py-1.5 rounded-full {{ $activeTab === 'backup_logs' ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-600' }}">{{ __('admin.backup_center.logs.backup_logs') }}</a>
                <a href="{{ route('admin.backup-center.logs.sync') }}" class="px-3 py-1.5 rounded-full {{ $activeTab === 'sync_logs' ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-600' }}">{{ __('admin.backup_center.logs.sync_logs') }}</a>
            </div>
        </div>
        <div class="p-6">
            @if($activeTab === 'sync_logs')
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-100 text-sm">
                        <thead class="bg-slate-50 text-slate-500">
                            <tr>
                                <th class="px-4 py-2 text-left">{{ __('admin.backup_center.logs.time') }}</th>
                                <th class="px-4 py-2 text-left">{{ __('admin.backup_center.logs.project') }}</th>
                                <th class="px-4 py-2 text-left">{{ __('admin.backup_center.logs.trigger_mechanism') }}</th>
                                <th class="px-4 py-2 text-left">{{ __('admin.backup_center.logs.executed_by') }}</th>
                                <th class="px-4 py-2 text-left">{{ __('admin.backup_center.logs.runtime_duration') }}</th>
                                <th class="px-4 py-2 text-left">{{ __('admin.backup_center.logs.status') }}</th>
                                <th class="px-4 py-2 text-left">{{ __('admin.backup_center.logs.cloud_path') }}</th>
                                <th class="px-4 py-2 text-left">{{ __('admin.backup_center.logs.message') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 text-slate-700">
                            @forelse($syncLogs as $log)
                                @php($triggerLabel = __('admin.backup_center.trigger.' . $log->trigger))
                                @php($statusLabel = __('admin.backup_center.status.' . $log->status))
                                <tr>
                                    <td class="px-4 py-3">{{ $log->created_at->format('Y-m-d H:i') }}</td>
                                    <td class="px-4 py-3">{{ optional($log->project)->name ?? ($log->project_id ? '#' . $log->project_id : '—') }}</td>
                                    <td class="px-4 py-3">{{ $triggerLabel }}</td>
                                    <td class="px-4 py-3">{{ $log->triggered_by ?? __('admin.backup_center.logs.system') }}</td>
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
                                    <td colspan="8" class="px-4 py-6 text-center text-slate-500">{{ __('admin.backup_center.logs.no_sync_logs') }}</td>
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
                                <th class="px-4 py-2 text-left">{{ __('admin.backup_center.logs.time') }}</th>
                                <th class="px-4 py-2 text-left">{{ __('admin.backup_center.logs.project') }}</th>
                                <th class="px-4 py-2 text-left">{{ __('admin.backup_center.logs.disk') }}</th>
                                <th class="px-4 py-2 text-left">{{ __('admin.backup_center.logs.executed_by') }}</th>
                                <th class="px-4 py-2 text-left">{{ __('admin.backup_center.logs.runtime_duration') }}</th>
                                <th class="px-4 py-2 text-left">{{ __('admin.backup_center.logs.file_fingerprint') }}</th>
                                <th class="px-4 py-2 text-left">{{ __('admin.backup_center.logs.status') }}</th>
                                <th class="px-4 py-2 text-left">{{ __('admin.backup_center.logs.message') }}</th>
                                <th class="px-4 py-2 text-left">{{ __('admin.backup_center.logs.actions') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 text-slate-700">
                            @forelse($backupLogs as $log)
                                @php($statusLabel = __('admin.backup_center.status.' . $log->status))
                                <tr>
                                    <td class="px-4 py-3">{{ $log->created_at->format('Y-m-d H:i') }}</td>
                                    <td class="px-4 py-3">{{ optional($log->project)->name ?? __('admin.backup_center.logs.statements_batch') }}</td>
                                    <td class="px-4 py-3">{{ strtoupper($log->disk) }}</td>
                                    <td class="px-4 py-3">{{ $log->triggered_by ?? __('admin.backup_center.logs.system') }}</td>
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
                                                <a href="{{ $log->cloud_url }}" target="_blank" class="px-2 py-1 text-xs font-semibold rounded bg-emerald-50 text-emerald-700 hover:bg-emerald-100">{{ __('admin.backup_center.logs.cloud_link') }}</a>
                                            @endif
                                            <form method="GET" action="{{ route('admin.backup-center.download') }}">
                                                <input type="hidden" name="disk" value="{{ $log->disk }}">
                                                <input type="hidden" name="path" value="{{ $log->file_name }}">
                                                <button type="submit" class="px-2 py-1 text-xs font-semibold rounded bg-slate-100 text-slate-600 hover:bg-slate-200">{{ __('admin.backup_center.logs.download') }}</button>
                                            </form>
                                            <form method="POST" action="{{ route('admin.backup-center.delete') }}" onsubmit="return confirm('{{ __('admin.backup_center.logs.delete_confirm') }}');">
                                                @csrf
                                                @method('DELETE')
                                                <input type="hidden" name="disk" value="{{ $log->disk }}">
                                                <input type="hidden" name="path" value="{{ $log->file_name }}">
                                                <button type="submit" class="px-2 py-1 text-xs font-semibold rounded bg-red-50 text-red-600 hover:bg-red-100">{{ __('admin.backup_center.logs.delete') }}</button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="9" class="px-4 py-6 text-center text-slate-500">{{ __('admin.backup_center.logs.no_backup_logs') }}</td>
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
