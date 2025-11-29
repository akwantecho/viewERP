<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use App\Models\Document;

class CustomerController extends Controller
{
   public function index(Request $request)
{
    $q = trim($request->input('q', ''));

    $customers = Customer::query()
        ->when($q !== '', function ($query) use ($q) {
            $query->where(function ($q2) use ($q) {
                $q2->where('name', 'like', "%{$q}%")
                   ->orWhere('phone', 'like', "%{$q}%")
                   ->orWhere('email', 'like', "%{$q}%")
                   ->orWhere('civil_number', 'like', "%{$q}%"); // ✅ البحث بالرقم المدني
            });
        })
        ->orderBy('name')
        ->paginate(15)
        ->appends(['q' => $q]);

    return view('customers.index', compact('customers', 'q'));
}


    public function create()
    {
        return view('customers.create');
    }

    public function store(Request $request)
    {
        // ملاحظة: national_id غير موجود في الجدول، أضفت له فاليديشن اختياري
        $validated = $request->validate([
            'name'          => 'required|string|max:255',
            'phone'         => 'required|string|max:255|unique:customers,phone',
            'email'         => 'nullable|email|max:255',
            'coming_from'   => 'nullable|string|max:255',
            'civil_number'  => 'required|string|max:255|unique:customers,civil_number',
            'id_type'       => 'nullable|array',
            'id_type.*'     => 'in:immigrant,passport',
            'id_file'       => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:5120',
        ], [
            'phone.unique' => 'Phone number is already used by another customer.',
            'civil_number.unique' => 'Civil ID is already used by another customer.',
        ]);

        return DB::transaction(function () use ($request, $validated) {
            // نخزن id_type كـ نص مفصول بفواصل لأن الحقل string
            $validated['id_type'] = isset($validated['id_type'])
                ? implode(',', $validated['id_type'])
                : null;

            // أنشئ العميل بدون الملفات أولاً للحصول على الـ id
            $customer = Customer::create([
                'name'          => $validated['name'],
                'phone'         => $validated['phone'],
                'email'         => $validated['email'] ?? null,
                'national_id'   => $validated['national_id'] ?? null,
                'coming_from'   => $validated['coming_from'] ?? null,
                'civil_number'  => $validated['civil_number'] ?? null,
                'id_type'       => $validated['id_type'],
            ]);

            /** @var \Illuminate\Filesystem\FilesystemAdapter $disk */
            $disk = Storage::disk('s3');

            // S3 directory named after the customer
            $safeName = preg_replace('/[^A-Za-z0-9\-_.]+/', '-', strtoupper($customer->name));
            $dir = "customers/{$safeName}";

            // رفع بطاقة/هوية
            if ($request->hasFile('id_file')) {
                $idFile      = $request->file('id_file');
                $ext         = strtolower($idFile->getClientOriginalExtension() ?: 'pdf');
                $civil       = preg_replace('/[^A-Za-z0-9\-_.]+/', '', (string) ($customer->civil_number ?? 'ID')) ?: ('ID'.time());
                $candidate   = $civil . '.' . $ext;
                // ensure unique name if exists
                $i = 1; $nameToUse = $candidate;
                try {
                    while ($disk->exists("$dir/$nameToUse") && $i < 50) {
                        $nameToUse = $civil . '-' . $i . '.' . $ext; $i++;
                    }
                } catch (\Throwable $e) {
                    \Log::error('Unhandled exception while generating unique customer ID filename', [
                        'location'    => __METHOD__,
                        'class'       => static::class,
                        'customer_id' => $customer->id ?? null,
                        'dir'         => $dir,
                        'message'     => $e->getMessage(),
                        'trace'       => $e->getTraceAsString(),
                    ]);

                    report($e);
                }
                $idPath      = $disk->putFileAs($dir, $idFile, $nameToUse, 'public');
                $customer->id_file = $disk->url($idPath);
                // سجل كمستند
                Document::create([
                    'name' => 'Customer ID',
                    'type' => 'id',
                    'path' => $customer->id_file,
                    'documentable_type' => \App\Models\Customer::class,
                    'documentable_id'   => $customer->id,
                ]);
            }

            $customer->save();

            return redirect()
                ->route('customers.index')
                ->with('success', 'Customer created successfully.');
        });
    }

    public function edit(Customer $customer)
    {
        return view('customers.edit', compact('customer'));
    }

    public function update(Request $request, Customer $customer)
    {
        $validated = $request->validate([
            'name'          => 'required|string|max:255',
            'phone'         => 'required|string|max:255|unique:customers,phone,' . $customer->id,
            'email'         => 'nullable|email|max:255',
            'coming_from'   => 'nullable|string|max:255',
            'civil_number'  => 'nullable|string|max:255|unique:customers,civil_number,' . $customer->id,
            'id_type'       => 'nullable|array',
            'id_type.*'     => 'in:immigrant,passport',
            'id_file'       => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:5120',
        ], [
            'phone.unique' => 'Phone number is already used by another customer.',
            'civil_number.unique' => 'Civil ID is already used by another customer.',
        ]);

        $validated['id_type'] = isset($validated['id_type'])
            ? implode(',', $validated['id_type'])
            : null;

        // تحديث البيانات النصية
        $customer->fill([
            'name'          => $validated['name'],
            'phone'         => $validated['phone'],
            'email'         => $validated['email'] ?? null,
            'coming_from'   => $validated['coming_from'] ?? null,
            'civil_number'  => $validated['civil_number'] ?? null,
            'id_type'       => $validated['id_type'],
        ]);

        /** @var \Illuminate\Filesystem\FilesystemAdapter $disk */
        $disk = Storage::disk('s3');

        $safeName = preg_replace('/[^A-Za-z0-9\-_.]+/', '-', strtoupper($customer->name));
        $dir = "customers/{$safeName}";

        // استبدال الملفات عند الرفع
        if ($request->hasFile('id_file')) {
            $idFile      = $request->file('id_file');
            $ext         = strtolower($idFile->getClientOriginalExtension() ?: 'pdf');
            $civil       = preg_replace('/[^A-Za-z0-9\-_.]+/', '', (string) ($validated['civil_number'] ?? $customer->civil_number ?? 'ID')) ?: ('ID'.time());
            $candidate   = $civil . '.' . $ext;
            $i=1; $nameToUse=$candidate;
            try {
                while ($disk->exists("$dir/$nameToUse") && $i < 50) { $nameToUse = $civil.'-'.$i.'.'.$ext; $i++; }
            } catch (\Throwable $e) {
                \Log::error('Unhandled exception while generating unique customer ID filename', [
                    'location'    => __METHOD__,
                    'class'       => static::class,
                    'customer_id' => $customer->id ?? null,
                    'dir'         => $dir,
                    'message'     => $e->getMessage(),
                    'trace'       => $e->getTraceAsString(),
                ]);

                report($e);
            }
            $idPath      = $disk->putFileAs($dir, $idFile, $nameToUse, 'public');
            $customer->id_file = $disk->url($idPath);
            Document::create([
                'name' => 'Customer ID',
                'type' => 'id',
                'path' => $customer->id_file,
                'documentable_type' => \App\Models\Customer::class,
                'documentable_id'   => $customer->id,
            ]);
        }

        $customer->save();

        return redirect()->route('customers.index')->with('success', 'Customer updated successfully.');
    }

    public function destroy(Customer $customer)
    {
        $customer->delete();
        return redirect()->route('customers.index')->with('success', 'Customer deleted.');
    }

    public function profile($id)
    {
        $customer = Customer::with([
            'bookings.unit.floor.project',
            'bookings.installments',
            'bookings.documents',
            'bookings.unit.documents',
            'documents'
        ])->findOrFail($id);

        $notesQuery = $customer->notes()
            ->with('user')
            ->orderByDesc('is_pinned')
            ->orderByDesc('pinned_at')
            ->orderByDesc('updated_at');

        $notes = (clone $notesQuery)->paginate(6)->withQueryString();

        $editingNote = null;
        $editingId = request()->query('note');
        if ($editingId) {
            $editingNote = (clone $notesQuery)->where('customer_notes.id', $editingId)->first();
        }

        return view('customers.profile', compact('customer', 'notes', 'editingNote'));
    }
}
