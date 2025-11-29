@extends('layouts.app')

@section('content')
<div class="max-w-6xl mx-auto p-6 bg-white rounded shadow space-y-4">
    <div class="flex items-center justify-between">
        <h1 class="text-xl font-bold">Automatic Backups — Total Statement</h1>
        <form method="POST" action="{{ route('sync.run') }}">
            @csrf
            <button class="bg-yellow-500 hover:bg-yellow-600 text-white px-4 py-2 rounded shadow">Run Now</button>
        </form>
    </div>
    <p class="text-sm text-gray-600">Schedule: Every Thursday at 17:00 — backs up each project's statement PDF to S3 (public). Below is the log of automatic/manual runs.</p>
    @php
        try {
            $codes = \App\Models\Project::orderBy('code')->pluck('code')->all();
        } catch (\Throwable $e) { $codes = []; }
        $count = count($codes);
        $codes = array_map(fn($c)=>strtoupper((string)$c), $codes);
        $preview = implode(', ', array_slice($codes, 0, 20));
        if ($count > 20) { $preview .= ' …'; }
    @endphp
    <div class="bg-gray-50 border rounded p-3 text-sm">
        <strong>Projects:</strong> {{ $count }}
        <span class="text-gray-600">({{ $preview }})</span>
    </div>

    @if(session('success'))
        <div class="bg-green-100 p-3 mt-4">{{ session('success') }}</div>
    @endif

    <table class="w-full mt-2 border text-sm">
        <thead>
            <tr class="bg-gray-100">
                <th class="p-2 border text-left">Time</th>
                <th class="p-2 border text-left">Local Path</th>
                <th class="p-2 border text-left">Remote Path</th>
                <th class="p-2 border">Status</th>
                <th class="p-2 border">Trigger</th>
                <th class="p-2 border text-left">Message</th>
            </tr>
        </thead>
        <tbody>
            @foreach($logs as $log)
            <tr>
                <td class="p-2 border whitespace-nowrap">{{ $log->created_at?->format('Y-m-d H:i') }}</td>
                <td class="p-2 border">{{ $log->local_path }}</td>
                <td class="p-2 border">
                    @php
                        $remote = $log->remote_path;
                        $url = null;
                        try {
                            if (config('filesystems.disks.s3')) {
                                $url = \Illuminate\Support\Facades\Storage::disk('s3')->url($remote);
                            } else {
                                $url = asset('storage/'.ltrim($remote,'/'));
                            }
                        } catch (\Throwable $e) { $url = null; }
                    @endphp
                    @if($url)
                        <a href="{{ $url }}" target="_blank" class="text-blue-600 underline">{{ $remote }}</a>
                    @else
                        {{ $remote }}
                    @endif
                </td>
                <td class="p-2 border"><span class="px-2 py-1 rounded text-xs {{ $log->status === 'success' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' }}">{{ ucfirst($log->status) }}</span></td>
                <td class="p-2 border">{{ ucfirst($log->trigger) }}</td>
                <td class="p-2 border">{{ $log->message }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="mt-4">{{ $logs->links() }}</div>
</div>
@endsection
