@extends('layouts.app')

@section('content')
<div class="max-w-7xl mx-auto bg-white p-6 rounded-xl shadow space-y-6" style="background-color:#f5f2e8;">

    {{-- Header + Global Actions --}}
    <div class="flex items-center justify-between">
        <h2 class="text-2xl font-bold" style="color:#434141;">Total Statement — All Projects</h2>

        <div class="flex gap-2 flex-wrap">
            {{-- تنزيل كل المشاريع PDF --}}
            <a href="{{ route('profile.totalStatementAll.pdf') }}"
               class="px-3 py-2 rounded-lg font-semibold" style="background:#626569;color:white;">
                Download All (PDF)
            </a>

            {{-- رفع جميع ستيتمنت المشاريع إلى Google Drive --}}
            <form action="{{ route('profile.totalStatementAll.backupAll') }}" method="POST" onsubmit="return confirm('Backup statements for all projects to remote disk?');">
                @csrf
                <button type="submit" class="px-3 py-2 rounded-lg font-semibold" style="background:#4b5563;color:white;">
                    Backup All to S3
                </button>
            </form>
        </div>
    </div>

    {{-- Alerts --}}
    @if(session('success'))
        <div class="p-3 rounded-lg" style="background:#f3f4f6;color:#1f2937;border:1px solid #6b7280;">
            {{ session('success') }}
        </div>
    @endif
    @if(session('error'))
        <div class="p-3 rounded-lg" style="background:#fdecea;color:#434141;border:1px solid #f5ce00;">
            {{ session('error') }}
        </div>
    @endif

    {{-- Projects Table --}}
    <div class="overflow-auto border rounded-lg bg-white">
        <table class="min-w-full text-sm">
            <thead style="background:#f3f3f3;color:#434141;">
                <tr>
                    <th class="px-4 py-3 text-left border">Project Name</th>
                    <th class="px-4 py-3 text-left border">Project Code</th>
                    <th class="px-4 py-3 text-center border">Units</th>
                    <th class="px-4 py-3 text-center border">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($rows as $row)
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-2 border">{{ $row['project_name'] }}</td>
                        <td class="px-4 py-2 border font-mono">{{ $row['project_code'] }}</td>
                        <td class="px-4 py-2 border text-center">{{ $row['units_count'] }}</td>
                        <td class="px-4 py-2 border">
                            <div class="flex items-center justify-center gap-2 flex-wrap">
                                {{-- فتح ستيتمنت المشروع --}}
                                <a href="{{ route('projects.statement', $row['project_id']) }}"
                                   class="px-3 py-2 rounded-lg font-semibold"
                                   style="background:#f5ce00;color:#1f2937;">
                                   Open Statement
                                </a>

                                {{-- تحميل PDF --}}
                                <a href="{{ route('projects.statement.pdf', $row['project_id']) }}"
                                   class="px-3 py-2 rounded-lg font-semibold"
                                   style="background:#626569;color:white;">
                                   Download PDF
                                </a>

                                {{-- Backup single project to S3 --}}
                                @if(auth()->user()?->is_super)
                                <form action="{{ route('projects.statement.backup', $row['project_id']) }}"
                                      method="POST" onsubmit="return confirm('Backup this project statement to remote disk?');" class="inline">
                                    @csrf
                                    <button type="submit"
                                            class="px-3 py-2 rounded-lg font-semibold"
                                            style="background:#4b5563;color:white;">
                                        Backup to S3
                                    </button>
                                </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td class="px-4 py-3 border text-center text-gray-500" colspan="4">
                            لا توجد مشاريع حتى الآن.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- Logs Table --}}
    <div class="space-y-3">
        <h3 class="text-lg font-bold" style="color:#434141;">Sync Logs</h3>

        <div class="overflow-auto border rounded-lg bg-white">
            <table class="min-w-full text-sm">
                <thead style="background:#f3f3f3;color:#434141;">
                    <tr>
                        <th class="px-3 py-3 text-left border">Time</th>
                        <th class="px-3 py-3 text-left border">Project</th>
                        <th class="px-3 py-3 text-left border">File</th>
                        <th class="px-3 py-3 text-center border">Size</th>
                        <th class="px-3 py-3 text-center border">Disk</th>
                        <th class="px-3 py-3 text-center border">Status</th>
                        <th class="px-3 py-3 text-left border">Message</th>
                        <th class="px-3 py-3 text-left border">By</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($logs as $log)
                        <tr class="hover:bg-gray-50">
                            <td class="px-3 py-2 border whitespace-nowrap">
                                {{ $log->created_at?->format('Y-m-d H:i') }}
                            </td>
                            <td class="px-3 py-2 border">
                                @if($log->project)
                                    {{ $log->project->name }} <span class="text-xs text-gray-500">({{ $log->project->code }})</span>
                                @else
                                    <span class="text-gray-500">—</span>
                                @endif
                            </td>
                            <td class="px-3 py-2 border">
                                {{ $log->file_name ?? '—' }}
                            </td>
                            <td class="px-3 py-2 border text-center">
                                @php
                                    $kb = $log->bytes ? number_format($log->bytes / 1024, 1) : 0;
                                @endphp
                                {{ $kb }} KB
                            </td>
                            <td class="px-3 py-2 border text-center">
                                {{ $log->disk ?? 'google' }}
                            </td>
                            <td class="px-3 py-2 border text-center">
                                @if($log->status === 'success')
                                    <span class="px-2 py-1 rounded text-xs font-semibold" style="background:#f3f4f6;color:#1f2937;border:1px solid #6b7280;">
                                        success
                                    </span>
                                @else
                                    <span class="px-2 py-1 rounded text-xs font-semibold" style="background:#fdecea;color:#434141;border:1px solid #f5ce00;">
                                        failed
                                    </span>
                                @endif
                            </td>
                            <td class="px-3 py-2 border">
                                {{ Str::limit($log->message, 120) }}
                            </td>
                            <td class="px-3 py-2 border">
                                {{ $log->ran_by ?? 'system' }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td class="px-3 py-3 border text-center text-gray-500" colspan="8">
                                لا توجد عمليات حتى الآن.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Pagination for logs --}}
        <div>
            {{ $logs->links() }}
        </div>
    </div>
</div>

{{-- منع الضغط المزدوج على أزرار الرفع --}}
<script>
document.addEventListener('submit', function(e){
    const btn = e.target.querySelector('button[type="submit"]');
    if (btn) {
        btn.disabled = true;
        btn.innerText = 'Processing...';
        setTimeout(() => { btn.disabled = false; }, 8000);
    }
});
</script>
@endsection
