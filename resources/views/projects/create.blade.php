@extends('layouts.app')

@section('content')
<div class="max-w-6xl mx-auto bg-white p-6 rounded shadow space-y-8">
    <h2 class="text-2xl font-bold text-gray-800 mb-4">{{ __('projects.form.create_title') }}</h2>

    @if ($errors->any())
        <div class="bg-red-50 border border-red-300 text-red-800 p-3 rounded text-sm">
            <ul class="list-disc ms-5">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ route('projects.store') }}" method="POST">
        @csrf

        {{-- Project Info --}}
        <div class="space-y-4">
            <h3 class="text-lg font-semibold text-gray-700">{{ __('projects.form.sections.info') }}</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm text-gray-600 mb-1">{{ __('projects.form.fields.name') }}</label>
                    <input type="text" name="name" value="{{ old('name') }}" class="w-full rounded border border-gray-300 px-4 py-2" required>
                </div>
                <div>
                    <label class="block text-sm text-gray-600 mb-1">{{ __('projects.form.fields.code') }}</label>
                    <input type="text" name="code" value="{{ old('code') }}" class="w-full rounded border border-gray-300 px-4 py-2 @error('code') border-red-400 @enderror" required>
                    @error('code')
                        <div class="text-xs text-red-600 mt-1">{{ $message }}</div>
                    @enderror
                </div>
                <div class="md:col-span-2">
                    <label class="block text-sm text-gray-600 mb-1">{{ __('projects.form.fields.notes') }}</label>
                    <textarea name="notes" rows="3" class="w-full rounded border border-gray-300 px-4 py-2">{{ old('notes') }}</textarea>
                </div>
            </div>
        </div>

        {{-- Floor & Units --}}
        <div class="space-y-4 mt-8">
            <h3 class="text-lg font-semibold text-gray-700">{{ __('projects.form.sections.structure') }}</h3>
            <div id="floors-wrapper" class="space-y-4">
                @php $oldFloors = old('floors'); @endphp
                @if(is_array($oldFloors))
                    @foreach ($oldFloors as $idx => $f)
                        @php
                            $fname = $f['name'] ?? '';
                            $naming = $f['naming'] ?? 'auto';
                            $units = $f['units'] ?? [];
                            $unitsCount = is_array($units) ? count($units) : (int) ($f['units_count'] ?? 0);
                        @endphp
                        <div class="p-4 bg-gray-50 border rounded relative">
                            <h4 class="text-sm font-bold text-gray-600">{{ __('projects.form.fields.floor_name') }}</h4>
                            <input type="text" name="floors[{{ $idx }}][name]" value="{{ $fname }}" class="border rounded w-40 mb-2 floor-name-input" required oninput="updateUnitNames({{ $idx }})">

                            <label class="text-sm text-gray-600 block">{{ __('projects.form.fields.naming_type') }}</label>
                            <select name="floors[{{ $idx }}][naming]" onchange="generateUnitsInputs(document.querySelector('input[name=\'floors[{{ $idx }}][units_count]\']'), {{ $idx }})" class="border rounded w-40 mb-2">
                                <option value="auto" {{ $naming === 'auto' ? 'selected' : '' }}>{{ __('projects.form.fields.auto') }}</option>
                                <option value="manual" {{ $naming === 'manual' ? 'selected' : '' }}>{{ __('projects.form.fields.manual') }}</option>
                            </select>

                            <label class="text-sm text-gray-600 block">{{ __('projects.form.fields.units_number') }}</label>
                            <input type="number" min="0" name="floors[{{ $idx }}][units_count]" value="{{ $unitsCount }}" class="border rounded border-gray-300 w-40 h-8" oninput="generateUnitsInputs(this, {{ $idx }})">

                            <div id="units-{{ $idx }}" class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-2 mt-2">
                                @if(is_array($units))
                                    @foreach ($units as $uIdx => $u)
                                        <input type="text" name="floors[{{ $idx }}][units][{{ $uIdx }}][name]" value="{{ $u['name'] ?? '' }}" class="border rounded px-2 py-1 w-full mb-1" {{ ($naming === 'auto') ? 'readonly' : '' }} required>
                                    @endforeach
                                @endif
                            </div>
                        </div>
                    @endforeach
                @else
                    @foreach (['L', 'G', '1'] as $floorName)
                        <div class="p-4 bg-gray-50 border rounded relative">
                            <h4 class="text-sm font-bold text-gray-600">{{ __('projects.form.fields.floor_name') }} {{ $floorName }}</h4>
                            <input type="hidden" name="floors[{{ $loop->index }}][name]" value="{{ $floorName }}">
                            <input type="hidden" name="floors[{{ $loop->index }}][naming]" value="auto">
                            <label class="block mt-2 text-sm text-gray-600">{{ __('projects.form.fields.units_number') }}</label>
                            <input type="number" name="floors[{{ $loop->index }}][units_count]" min="0"
                                class="border rounded border-gray-300 w-40 h-8"
                                oninput="generateUnitsInputs(this, {{ $loop->index }})">
                            <div id="units-{{ $loop->index }}" class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-2 mt-2"></div>
                        </div>
                    @endforeach
                @endif
            </div>

            <button type="button" onclick="addFloor()" class="bg-green-600 text-white px-4 py-2 rounded shadow hover:bg-green-700">
                + {{ __('projects.form.buttons.add_floor') }}
            </button>
        </div>

        <div class="mt-6">
            <button type="submit" class="bg-yellow-500 text-white px-6 py-2 rounded hover:bg-yellow-600">
                {{ __('projects.form.buttons.save') }}
            </button>
        </div>
    </form>
</div>

{{-- JavaScript --}}
<script>
    // Start the index after the largest old floor index (if any), else 2 -> next is 3
    @php
        $oldIndexes = is_array(old('floors')) ? array_keys(old('floors')) : [];
        $maxIdx = $oldIndexes ? max($oldIndexes) : 2; // defaults to indices 0,1,2
    @endphp
    let floorIndex = {{ (int) $maxIdx + 1 }};

    function addFloor() {
        const wrapper = document.getElementById('floors-wrapper');
        const index = floorIndex++;

        const floorDiv = document.createElement('div');
        floorDiv.className = "p-4 bg-gray-50 border rounded relative";
        floorDiv.innerHTML = `
            <button type="button" onclick="this.parentElement.remove()"
                class="absolute top-2 right-2 text-red-600 hover:text-red-800 font-bold text-xl">&times;</button>

            <label class="text-sm font-bold text-gray-700 block mb-2">{{ __('projects.form.fields.floor_name') }}</label>
            <input type="text" name="floors[${index}][name]" class="border rounded w-40 mb-2 floor-name-input" required oninput="updateUnitNames(${index})">

            <label class="text-sm text-gray-600 block">{{ __('projects.form.fields.naming_type') }}</label>
            <select name="floors[${index}][naming]" onchange="generateUnitsInputs(document.querySelector('input[name=\'floors[${index}][units_count]\']'), ${index})"
                class="border rounded w-40 mb-2">
                <option value="auto" selected>{{ __('projects.form.fields.auto') }}</option>
                <option value="manual">{{ __('projects.form.fields.manual') }}</option>
            </select>

            <label class="text-sm text-gray-600 block">{{ __('projects.form.fields.units_number') }}</label>
            <input type="number" min="1" class="border rounded w-40 mb-2"
                name="floors[${index}][units_count]" oninput="generateUnitsInputs(this, ${index})">

            <div id="units-${index}" class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-2 mt-2"></div>
        `;
        wrapper.appendChild(floorDiv);
    }

    function generateUnitsInputs(input, index) {
        const count = parseInt(input.value) || 0;
        const container = document.getElementById(`units-${index}`);
        const floorNameInput = document.querySelector(`input[name="floors[${index}][name]"]`);
        const namingSelect = document.querySelector(`select[name="floors[${index}][naming]"]`);
        const namingType = namingSelect?.value || 'auto';
        const floorCode = floorNameInput?.value || `F${index}`;

        container.innerHTML = '';
        for (let i = 0; i < count; i++) {
            const unitInput = document.createElement('input');
            unitInput.type = 'text';
            unitInput.name = `floors[${index}][units][${i}][name]`;
            unitInput.className = "border rounded px-2 py-1 w-full mb-1";
            unitInput.required = true;

            if (namingType === 'auto') {
                unitInput.value = `${floorCode}-${String(i + 1).padStart(2, '0')}`;
                unitInput.readOnly = true;
            } else {
                unitInput.placeholder = "{{ __('forms.placeholders.enter_unit_code') }}";
                unitInput.readOnly = false;
            }

            container.appendChild(unitInput);
        }
    }

    function updateUnitNames(index) {
        const floorName = document.querySelector(`input[name="floors[${index}][name]"]`).value;
        const unitInputs = document.querySelectorAll(`input[name^="floors[${index}][units]"][name$="[name]"]`);
        const namingSelect = document.querySelector(`select[name="floors[${index}][naming]"]`);
        const namingType = namingSelect?.value || 'auto';

        if (namingType === 'auto') {
            unitInputs.forEach((input, i) => {
                input.value = `${floorName}-${String(i + 1).padStart(2, '0')}`;
            });
        }
    }
</script>
@endsection
