@extends('layouts.app')

@section('content')
<div class="w-full px-6 py-10">
    <h1 class="text-2xl font-bold text-[#1f2937] mb-6">
        Financial Statement - {{ $project->name }} ({{ $project->code }})
    </h1>

    <div class="overflow-x-auto bg-white shadow rounded-xl p-6">
        <div class="flex justify-between items-center mb-4">
            <h2 class="text-xl font-bold text-[#1f2937]">Project Financial Statement</h2>
            <a href="{{ route('projects.statement.export', $project->id) }}" 
   class="px-4 py-2 bg-[#4b5563] text-white rounded-lg hover:bg-gray-800 text-sm">
   📄 Download PDF
</a>

        </div>

        @php
            $floorColors = ['bg-white', 'bg-gray-50', 'bg-gray-100', 'bg-gray-200', 'bg-gray-300', 'bg-gray-100', 'bg-gray-50'];
            $floorColorMap = [];
            $colorIndex = 0;

            $totalPaid = 0;
            $totalPrice = 0;
        @endphp

        <table class="w-full table-auto border text-sm text-center">
            <thead class="bg-gray-100 text-gray-700">
                <tr>
                    <th class="border px-4 py-2">Project Code</th>
                    <th class="border px-4 py-2">Floor</th>
                    <th class="border px-4 py-2">Unit</th>
                    <th class="border px-4 py-2">Paid Amount</th>
                    <th class="border px-4 py-2">Sale Price</th>
                    <th class="border px-4 py-2">Progress %</th>
                    <th class="border px-4 py-2">Details</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($units as $unit)
                    @php
                        $floorName = $unit->floor->name ?? 'unknown';
                        if (!isset($floorColorMap[$floorName])) {
                            $floorColorMap[$floorName] = $floorColors[$colorIndex % count($floorColors)];
                            $colorIndex++;
                        }
                        $rowColor = $floorColorMap[$floorName];

                        // Paid = sum of all payments recorded for this booking (advance + installments)
                        $paid = $unit->booking ? $unit->booking->payments->sum('amount') : 0;
                        $price = $unit->booking->total_price ?? 0;
                        $progress = $price > 0 ? round(($paid / $price) * 100, 2) : 0;

                        $totalPaid += $paid;
                        $totalPrice += $price;
                    @endphp

                    <tr class="{{ $rowColor }} hover:bg-opacity-75">
                        <td class="border px-4 py-2">{{ $unit->floor->project->code ?? '-' }}</td>
                        <td class="border px-4 py-2">{{ $floorName }}</td>
                        <td class="border px-4 py-2">{{ $unit->unit_code }}</td>
                        <td class="border px-4 py-2 text-gray-700 font-semibold">
                            {{ number_format($paid, 2) }} OMR
                        </td>
                        <td class="border px-4 py-2">{{ number_format($price, 2) }} OMR</td>
                        <td class="border px-4 py-2">{{ $progress }}%</td>
                        <td class="border px-4 py-2">
                            <a href="{{ route('units.show', $unit->id) }}" class="text-blue-600 hover:underline">View</a>
                        </td>
                    </tr>
                @endforeach
            </tbody>
            <tfoot class="bg-gray-200 font-bold">
                <tr>
                    <td colspan="3" class="border px-4 py-2 text-right">Total</td>
                    <td class="border px-4 py-2 text-gray-700">{{ number_format($totalPaid, 2) }} OMR</td>
                    <td class="border px-4 py-2">{{ number_format($totalPrice, 2) }} OMR</td>
                    <td class="border px-4 py-2">
                        {{ $totalPrice > 0 ? round(($totalPaid / $totalPrice) * 100, 2) : 0 }}%
                    </td>
                    <td class="border px-4 py-2">—</td>
                </tr>
            </tfoot>
        </table>
    </div>
</div>
@endsection
