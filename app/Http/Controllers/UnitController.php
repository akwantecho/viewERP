<?php

namespace App\Http\Controllers;

use App\Models\Unit;
use Illuminate\Http\Request;

class UnitController extends Controller
{
    /**
     * عرض جميع الوحدات
     */
    public function index(Request $request)
{
    $query = Unit::with(['floor.project', 'customer']);

    if ($search = $request->input('search')) {
        $query->whereHas('customer', function ($q) use ($search) {
            $q->where('civil_number', 'like', "%$search%")
              ->orWhere('phone', 'like', "%$search%")
              ->orWhere('id_type', 'like', "%$search%"); // نفترض أن الجواز ضمن id_type
        });
    }

    $units = $query->latest()->paginate(20);

    return view('units.index', compact('units'));
}


    /**
     * عرض تفاصيل وحدة محددة
     */
    public function show($id)
    {
$unit = Unit::with(['floor.project', 'customer'])->findOrFail($id);
$booking = $unit->booking()->with('installments')->latest()->first();
return view('units.show', compact('unit', 'booking'));
    }

    /**
     * عرض صفحة تعديل سعر الوحدة يدويًا
     */
    public function editPrice(Unit $unit)
    {
        return view('units.edit-price', compact('unit'));
    }

    /**
     * تحديث سعر الوحدة
     */
    public function updatePrice(Request $request, Unit $unit)
    {
        $validated = $request->validate([
            'base_price' => 'required|numeric|min:0',
            'area_sqm'   => 'nullable|numeric|min:0',
        ]);

        $unit->update([
            'base_price' => $validated['base_price'],
            'area_sqm'   => $validated['area_sqm'] ?? null,
        ]);

        return redirect()->back()->with('success', 'Unit price updated successfully.');
    }

    /**
     * عرض نموذج الحجز (يتم الحجز من خلال BookingController)
     */
    public function bookingForm($id)
    {
        $unit = Unit::with('floor.project')->findOrFail($id);

        // تأكد أن الوحدة متاحة للحجز
        if ($unit->status !== 'available') {
            return redirect()->route('units.show', $unit->id)->with('error', 'This unit is not available for booking.');
        }

        return view('units.booking', compact('unit'));
    }
}
