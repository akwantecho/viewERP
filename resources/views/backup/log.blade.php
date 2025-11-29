@extends('layouts.app')

@section('content')
<div class="space-y-6">
    <div>
        <h1 class="text-2xl font-semibold text-slate-800">{{ __('Backup Logs') }}</h1>
        <p class="text-sm text-slate-500">{{ __('Recent statement/backup operations across projects.') }}</p>
    </div>

    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-100 text-sm">
                <thead class="bg-slate-50 text-slate-500 uppercase text-xs tracking-wide">
                    <tr>
                        <th class="px-4 py-3 text-left">{{ __('Timestamp') }}</th>
                        <th class="px-4 py-3 text-left">{{ __('Project') }}</th>
                        <th class="px-4 py-3 text-left">{{ __('File') }}</th>
                        <th class="px-4 py-3 text-left">{{ __('Disk') }}</th>
                        <th class="px-4 py-3 text-left">{{ __('Bytes') }}</th>
                        <th class="px-4 py-3 text-left">{{ __('Status') }}</th>
                        <th class="px-4 py-3 text-left">{{ __('Trigger') }}</th>
                        <th class="px-4 py-3 text-left">{{ __('Message') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($logs as $log)
                        <tr class="hover:bg-slate-50">
                            <td class="px-4 py-3 text-slate-700">{{ optional($log->created_at)->format('Y-m-d H:i') }}</td>
                            <td class="px-4 py-3 text-slate-700">{{ $log->project->name ?? '—' }}</td>
                            <td class="px-4 py-3 text-slate-700 break-all">{{ $log->file_name ?? '—' }}</td>
                            <td class="px-4 py-3 text-slate-700">{{ $log->disk ?? '—' }}</td>
                            <td class="px-4 py-3 text-slate-700">
                                {{ $log->bytes ? number_format($log->bytes / 1024, 1) . ' KB' : '—' }}
                            </td>
                            <td class="px-4 py-3">
                                @if($log->status === 'success')
                                    <span class="rounded-full bg-emerald-100 px-3 py-1 text-xs font-semibold text-emerald-700">{{ __('Success') }}</span>
                                @elseif($log->status === 'failed')
                                    <span class="rounded-full bg-red-100 px-3 py-1 text-xs font-semibold text-red-700">{{ __('Failed') }}</span>
                                @else
                                    <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-600">{{ ucfirst($log->status ?? 'unknown') }}</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-slate-700">{{ ucfirst($log->trigger ?? '—') }}</td>
                            <td class="px-4 py-3 text-slate-600 max-w-xs">
                                <span title="{{ $log->message }}">{{ \Illuminate\Support\Str::limit($log->message ?? '—', 60) }}</span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-4 py-6 text-center text-slate-500">{{ __('No backup activity recorded yet.') }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="border-t border-slate-100 px-4 py-3">
            {{ $logs->links() }}
        </div>
    </div>
</div>
@endsection
