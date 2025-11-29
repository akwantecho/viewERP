@extends('layouts.app')

@section('content')
<div class="max-w-6xl mx-auto bg-white p-6 rounded shadow space-y-8">
    <h2 class="text-2xl font-bold text-gray-800 mb-4">{{ __('projects.form.edit_title') }}</h2>

    @if (session('success'))
        <div class="bg-green-50 border border-green-300 text-green-800 p-3 rounded text-sm whitespace-pre-line">
            {{ session('success') }}
        </div>
    @endif
    @if (session('warning'))
        <div class="bg-yellow-50 border border-yellow-300 text-yellow-900 p-3 rounded text-sm">
            {{ session('warning') }}
        </div>
    @endif
    @if ($errors->any())
        <div class="bg-red-100 border border-red-400 text-red-700 p-4 rounded">
            <ul class="list-disc pl-5 text-sm">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ route('projects.update', $project->id) }}" method="POST" id="projectEditForm">
        @csrf
        @method('PUT')

        {{-- Project Info --}}
        <div class="space-y-4">
            <h3 class="text-lg font-semibold text-gray-700">{{ __('projects.form.sections.info') }}</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm text-gray-600 mb-1">{{ __('projects.form.fields.name') }}</label>
                    <input type="text" name="name" value="{{ old('name', $project->name) }}"
                           class="w-full rounded-lg border border-gray-300 px-4 py-2 text-sm focus:outline-none focus:ring-2 shadow-sm"
                           required>
                </div>
                <div>
                    <label class="block text-sm text-gray-600 mb-1">{{ __('projects.form.fields.code') }}</label>
                    <input type="text" name="code" value="{{ old('code', $project->code) }}"
                           class="w-full rounded-lg border border-gray-300 px-4 py-2 text-sm focus:outline-none focus:ring-2 shadow-sm"
                           required>
                </div>
                <div class="md:col-span-2">
                    <label class="block text-sm text-gray-600 mb-1">{{ __('projects.form.fields.notes') }}</label>
                    <textarea name="notes" rows="3"
                              class="w-full rounded-lg border border-gray-300 px-4 py-2 text-sm focus:outline-none focus:ring-2 shadow-sm">{{ old('notes', $project->notes) }}</textarea>
                </div>
            </div>
        </div>

        {{-- Floors & Units --}}
        <div class="space-y-4 mt-8">
            <div class="flex items-center justify-between">
                <h3 class="text-lg font-semibold text-gray-700">{{ __('projects.form.labels.floors_units') }}</h3>
                <button type="button" class="px-3 py-2 rounded bg-green-600 text-white text-sm" onclick="addFloor()">+ {{ __('projects.form.buttons.add_floor') }}</button>
            </div>

            <div id="floorsContainer" class="space-y-6">
                @foreach($project->floors as $fIndex => $floor)
                    <div class="border rounded-lg p-4" data-floor>
                        <div class="flex items-center justify-between gap-3">
                            <div class="flex-1">
                                <label class="block text-sm text-gray-600 mb-1">{{ __('projects.form.fields.floor_name') }}</label>
                                <input type="hidden" name="floors[{{ $fIndex }}][id]" value="{{ $floor->id }}">
                                <input type="text" name="floors[{{ $fIndex }}][name]" value="{{ $floor->name }}" class="w-full border rounded px-3 py-2 text-sm" />
                            </div>
                            <div class="pt-6">
                                <label class="inline-flex items-center gap-2 text-red-700 text-sm">
                                    <input type="checkbox" name="floors[{{ $fIndex }}][delete]" value="1" class="rounded">
                                    {{ __('projects.form.labels.delete_floor') }}
                                </label>
                            </div>
                        </div>

                        <div class="mt-4">
                            <div class="flex items-center justify-between">
                                <h4 class="font-medium">{{ __('projects.form.labels.units') }}</h4>
                                <button type="button" class="px-2 py-1 rounded bg-blue-600 text-white text-xs" onclick="addUnit(this)">+ {{ __('projects.form.buttons.add_unit') }}</button>
                            </div>
                            <div class="space-y-2 mt-2" data-units>
                                @foreach($floor->units as $uIndex => $unit)
                                    @php $locked = in_array($unit->status, ['reserved','sold']); @endphp
                                    <div class="flex items-center gap-2" data-unit-row>
                                        <input type="hidden" name="floors[{{ $fIndex }}][units][{{ $uIndex }}][id]" value="{{ $unit->id }}">
                                        <input type="text" name="floors[{{ $fIndex }}][units][{{ $uIndex }}][unit_code]" value="{{ $unit->unit_code }}" class="border rounded px-2 py-1 text-sm flex-1" {{ $locked ? 'readonly' : '' }}>
                                        <span class="text-xs px-2 py-1 rounded {{ $locked ? 'bg-yellow-100 text-yellow-800' : 'bg-green-100 text-green-700' }}">{{ __('common.status.' . ($unit->status ?? 'available')) }}</span>
                                        <label class="inline-flex items-center gap-1 text-xs text-red-700">
                                            <input type="checkbox" name="floors[{{ $fIndex }}][units][{{ $uIndex }}][delete]" value="1" class="rounded" {{ $locked ? 'disabled' : '' }}>
                                            {{ __('buttons.delete') }}
                                        </label>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        <div class="mt-8 flex items-center gap-3">
            <button type="submit" class="bg-yellow-500 text-white px-6 py-2 rounded hover:bg-yellow-600">{{ __('projects.form.buttons.update') }}</button>
            <a href="{{ route('projects.show', $project->id) }}" class="text-sm text-gray-600 underline">{{ __('buttons.cancel') }}</a>
        </div>
    </form>
</div>
@push('scripts')
<script>
    let floorIndex = {{ count($project->floors) }};

    function addFloor() {
        const container = document.getElementById('floorsContainer');
        const div = document.createElement('div');
        div.className = 'border rounded-lg p-4';
        div.setAttribute('data-floor','');
        div.innerHTML = `
            <div class=\"flex items-center justify-between gap-3\">
                <div class=\"flex-1\">
                    <label class=\"block text-sm text-gray-600 mb-1\">{{ __('projects.form.fields.floor_name') }} </label>
                    <input type=\"text\" name=\"floors[${floorIndex}][name]\" class=\"w-full border rounded px-3 py-2 text-sm\" placeholder=\"e.g., F1\" />
                </div>
            </div>
            <div class=\"mt-4\">
                <div class=\"flex items-center justify-between\">
                    <h4 class=\"font-medium\">{{ __('projects.form.labels.units') }}</h4>
                    <button type=\"button\" class=\"px-2 py-1 rounded bg-blue-600 text-white text-xs\" onclick=\"addUnit(this)\">+ {{ __('projects.form.buttons.add_unit') }}</button>
                </div>
                <div class=\"space-y-2 mt-2\" data-units></div>
            </div>
        `;
        container.appendChild(div);
        floorIndex++;
    }

    function addUnit(buttonEl) {
        const floorBox = buttonEl.closest('[data-floor]');
        const unitsBox = floorBox.querySelector('[data-units]');
        const fNameInput = floorBox.querySelector('input[name^="floors["]');
        const match = fNameInput && fNameInput.name.match(/floors\[(\d+)\]/);
        const fIdx = match ? match[1] : floorIndex - 1;
        const uIdx = unitsBox.querySelectorAll('[data-unit-row]').length;
        const floorNameInput = floorBox.querySelector(`input[name="floors[${fIdx}][name]"]`);
        const floorCode = (floorNameInput?.value || `F${fIdx}`).toString().toUpperCase();
        const projectCode = "{{ strtoupper($project->code) }}";
        const row = document.createElement('div');
        row.className = 'flex items-center gap-2';
        row.setAttribute('data-unit-row','');
        row.innerHTML = `
            <input type=\"text\" name=\"floors[${fIdx}][units][${uIdx}][unit_code]\" class=\"border rounded px-2 py-1 text-sm flex-1\" value=\"${projectCode}-${floorCode}-${String(uIdx+1).padStart(2,'0')}\" />
            <span class=\"text-xs px-2 py-1 rounded bg-green-100 text-green-700\">{{ __('common.status.available') }}</span>
            <label class=\"inline-flex items-center gap-1 text-xs text-red-700\">
                <input type=\"checkbox\" name=\"floors[${fIdx}][units][${uIdx}][delete]\" value=\"1\" class=\"rounded\">
                {{ __('buttons.delete') }}
            </label>
        `;
        unitsBox.appendChild(row);
    }
</script>
@endpush
@endsection
