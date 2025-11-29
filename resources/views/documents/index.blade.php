@extends('layouts.app')

@section('content')
<div class="max-w-4xl mx-auto p-6 space-y-6">
    <h2 class="text-2xl font-bold text-[#1f2937]">📁 Unit Documents: {{ $unit->unit_code }}</h2>

    @if(session('success'))
        <div class="p-3 rounded-lg border border-green-300 bg-green-50 text-green-800">{{ session('success') }}</div>
    @endif
    @if($errors->any())
        <div class="p-3 rounded-lg border border-red-300 bg-red-50 text-red-700">
            <ul class="list-disc ms-5">
                @foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach
            </ul>
        </div>
    @endif

    <div class="bg-white rounded-xl shadow p-4">
        <h3 class="text-lg font-semibold mb-3">Upload New Document</h3>
        <form method="POST" action="{{ route('units.documents.store', $unit->id) }}" enctype="multipart/form-data" class="space-y-3">
            @csrf
            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                <div>
                    <label class="block text-sm font-medium">Name</label>
                    <input type="text" name="name" class="w-full border rounded px-3 py-2" required>
                </div>
                <div>
                    <label class="block text-sm font-medium">Type</label>
                    <select name="type" class="w-full border rounded px-3 py-2" required>
                        <option value="contract">Contract</option>
                        <option value="id">ID</option>
                        <option value="payment_receipt">Payment Receipt</option>
                        <option value="other">Other</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium">Attach To</label>
                    <select name="attach_to" class="w-full border rounded px-3 py-2" required>
                        <option value="unit">Unit</option>
                        <option value="booking" {{ $unit->booking ? '' : 'disabled' }}>Current Booking</option>
                    </select>
                    @unless($unit->booking)
                        <div class="text-xs text-gray-500 mt-1">This unit has no active booking.</div>
                    @endunless
                </div>
                <div>
                    <label class="block text-sm font-medium">File (PDF/JPG/PNG)</label>
                    <input type="file" name="file" accept=".pdf,.jpg,.jpeg,.png" class="w-full border rounded px-3 py-2" required>
                </div>
            </div>
            <div>
                <button class="bg-[#18ab69] hover:bg-[#0d804f] text-white px-4 py-2 rounded">Upload</button>
            </div>
        </form>
    </div>

    <div class="bg-white rounded-xl shadow p-4">
        @if ($documents->count())
            <table class="w-full border">
                <thead>
                    <tr class="bg-gray-100">
                        <th class="p-2">Name</th>
                        <th class="p-2">Type</th>
                        <th class="p-2">Download Link</th>
                        <th class="p-2 text-center">Actions</th>
                    </tr>
                </thead>
                <tbody>
                @forelse($documents as $doc)
                    @php
                        $docUrl = null;
                        if (!empty($doc->path)) {
                            $docUrl = \Illuminate\Support\Str::startsWith($doc->path, ['http://','https://'])
                                ? $doc->path
                                : \Illuminate\Support\Facades\Storage::url($doc->path);
                        }
                        $typeLabel = $doc->type === 'payment_receipt' ? 'Advance Receipt' : ucfirst($doc->type ?? '');
                    @endphp
                    <tr>
                        <td class="p-2">{{ $doc->name }}</td>
                        <td class="p-2">{{ $typeLabel }}</td>
                        <td class="p-2">
                            @if($docUrl)
                                <a href="{{ $docUrl }}" target="_blank" rel="noopener" class="text-blue-600 underline">View / Download</a>
                            @else
                                <span class="text-gray-400">—</span>
                            @endif
                        </td>
                        <td class="p-2 text-center">
                            <form method="POST" action="{{ route('units.documents.destroy', [$unit->id, $doc->id]) }}" data-confirm="delete" data-confirm-title="Delete Document" data-confirm-message="Are you sure you want to delete '{{ $doc->name }}'? This action cannot be undone." class="inline">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="px-3 py-1 text-white bg-red-600 hover:bg-red-700 rounded text-xs">Delete</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="p-4 text-center text-gray-500">No documents.</td></tr>
                @endforelse
                </tbody>
            </table>

            <div class="mt-4">
                {{ $documents->links() }}
            </div>
        @else
            <div class="text-gray-500 text-center py-10">No documents for this unit yet.</div>
        @endif
    </div>
    <!-- Delete Confirmation Modal -->
    <div id="confirmOverlay" class="fixed inset-0 bg-black/40 hidden items-center justify-center z-50">
        <div class="bg-white rounded-xl shadow-lg w-full max-w-md p-5">
            <h3 class="text-lg font-semibold text-gray-800">Delete Document</h3>
            <p class="text-sm text-gray-600 mt-2">Are you sure you want to delete <span id="docName" class="font-semibold"></span>? This action cannot be undone.</p>
            <div class="mt-5 flex items-center justify-end gap-2">
                <button type="button" class="px-4 py-2 rounded border border-gray-300 text-gray-700 hover:bg-gray-50" onclick="closeDeleteModal()">Cancel</button>
                <form id="deleteForm" method="POST" action="#">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="px-4 py-2 rounded bg-red-600 hover:bg-red-700 text-white">Delete</button>
                </form>
            </div>
        </div>
    </div>

    <script>
        function openDeleteModal(actionUrl, name) {
            document.getElementById('deleteForm').setAttribute('action', actionUrl);
            document.getElementById('docName').textContent = name || 'this document';
            const ov = document.getElementById('confirmOverlay');
            ov.classList.remove('hidden');
            ov.classList.add('flex');
        }
        function closeDeleteModal() {
            const ov = document.getElementById('confirmOverlay');
            ov.classList.add('hidden');
            ov.classList.remove('flex');
        }
    </script>
</div>
@endsection
