@extends('layouts.app')

@section('content')
<div class="max-w-7xl mx-auto px-4 py-6">
    <h2 class="text-2xl font-bold text-[#434141] mb-6">Units Management</h2>
<div class="mb-4">
    <form method="GET" action="{{ route('units.index') }}" class="flex items-center space-x-2">
        <input type="text" name="search" value="{{ request('search') }}"
            placeholder="Search by Civil Number, Passport or Phone"
            class="w-64 px-3 py-2 border border-gray-300 rounded shadow-sm text-sm focus:outline-none">
        <button type="submit"
            class="bg-green-600 text-white px-4 py-2 rounded shadow hover:bg-green-700">
            Search
        </button>
    </form>
</div>

    <div class="bg-white p-4 rounded shadow overflow-x-auto">
        <table class="min-w-full table-auto border-collapse border border-gray-200 text-sm">
            <thead>
                <tr class="bg-gray-100 text-left">
                    <th class="p-2 border">#</th>
                    <th class="p-2 border">Unit Code</th>
                    <th class="p-2 border">Project</th>
                    <th class="p-2 border">Floor</th>
                    <th class="p-2 border">Price (OMR)</th>
                    <th class="p-2 border">Status</th>
                    <th class="p-2 border">Customer</th>
                    <th class="p-2 border text-center">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($units as $index => $unit)
                    <tr class="hover:bg-[#f5f2e8]">
                        <td class="p-2 border">{{ $units->firstItem() + $index }}</td>
                        <td class="p-2 border font-medium">{{ $unit->unit_code }}</td>
                        <td class="p-2 border">{{ $unit->floor->project->name ?? '-' }}</td>
                        <td class="p-2 border">{{ $unit->floor->name ?? '-' }}</td>
                        <td class="p-2 border">{{ number_format($unit->base_price ?? 0, 2) }}</td>
                        <td class="p-2 border">
                            @if($unit->status === 'sold')
                                <span class="text-red-600 font-semibold">Sold</span>
                            @elseif($unit->status === 'reserved')
                                <span class="text-yellow-600 font-semibold">Reserved</span>
                            @else
                                <span class="text-green-600 font-semibold">Available</span>
                            @endif
                        </td>
                        <td class="p-2 border">
                            {{ $unit->customer->name ?? '-' }}
                        </td>
                        <td class="p-2 border text-center">
                            <a href="{{ route('units.show', $unit->id) }}"
                               class="text-blue-600 hover:underline">View</a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="p-4 text-center text-gray-500">No units found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        <div class="mt-4">
            {{ $units->links() }}
        </div>
    </div>
</div>
@endsection
