<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Document;
use App\Models\Unit;
use App\Models\Booking;
use Illuminate\Support\Facades\Storage;

class DocumentController extends Controller
{
    /**
     * عرض مستندات الوحدة.
     */
    public function index(Request $request, Unit $unit)
    {
        $request->validate([
            'type' => ['nullable', 'in:contract,id,payment_receipt,other']
        ]);

        // If documents table doesn't exist yet, return empty paginator gracefully
        if (!\Illuminate\Support\Facades\Schema::hasTable('documents')) {
            $paginator = new \Illuminate\Pagination\LengthAwarePaginator(
                collect(),
                0,
                15,
                1,
                ['path' => $request->url(), 'query' => $request->query()]
            );
            return view('documents.index', [
                'unit' => $unit,
                'documents' => $paginator,
            ]);
        }

        // Collect documents from unit, booking and customer (avoid arrow fn for compatibility)
        $unitDocsQuery = $unit->documents();
        if ($request->filled('type')) {
            $unitDocsQuery->where('type', $request->input('type'));
        }
        $unitDocs = $unitDocsQuery->get();

        $booking = $unit->booking;
        $bookingDocs = collect();
        if ($booking) {
            $bookingDocsQuery = $booking->documents();
            if ($request->filled('type')) {
                $bookingDocsQuery->where('type', $request->input('type'));
            }
            $bookingDocs = $bookingDocsQuery->get();
        }

        $customer = $booking?->customer;
        $customerDocs = collect();
        if ($customer) {
            $customerDocsQuery = $customer->documents();
            if ($request->filled('type')) {
                $customerDocsQuery->where('type', $request->input('type'));
            }
            $customerDocs = $customerDocsQuery->get();
        }

        $all = $unitDocs->merge($bookingDocs)->merge($customerDocs)
            ->sortByDesc('created_at')
            ->values();

        // Simple manual pagination for merged collection
        $perPage = 15;
        $page = (int) ($request->input('page', 1));
        $items = $all->forPage($page, $perPage);
        $paginator = new \Illuminate\Pagination\LengthAwarePaginator(
            $items,
            $all->count(),
            $perPage,
            $page,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        return view('documents.index', [
            'unit' => $unit,
            'documents' => $paginator,
        ]);
    }


    /**
     * حفظ مستند جديد للوحدة.
     */
    public function store(Request $request, Unit $unit)
    {
        $data = $request->validate([
            'name'       => ['required', 'string', 'max:255'],
            'type'       => ['required', 'in:contract,id,payment_receipt,other'],
            'attach_to'  => ['required', 'in:unit,booking'],
            'file'       => ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:20480'], // 20MB
        ]);

        // Ensure documents table exists to avoid SQL errors if migrations haven't run yet
        if (!\Illuminate\Support\Facades\Schema::hasTable('documents')) {
            return back()->withErrors(['error' => 'Documents table not found. Please run migrations (php artisan migrate).']);
        }

        // رفع الملف إلى S3 باسم يتضمن كود/اسم الوحدة
        $dir = $data['attach_to'] === 'booking'
            ? "documents/bookings/" . ($unit->booking->id ?? 'no-booking')
            : "documents/units/{$unit->id}";

        $disk = 's3';
        $file = $request->file('file');
        $ext = strtolower($file->getClientOriginalExtension() ?: 'bin');
        $unitCode = (string) ($unit->unit_code ?? ('UNIT-'.$unit->id));
        // sanitize unit code and label to be filesystem friendly
        $label = $data['name'] ?? pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $safeUnit = preg_replace('/[^A-Za-z0-9\-_.]+/', '-', $unitCode);
        $safeLabel = preg_replace('/[^A-Za-z0-9\-_.]+/', '-', (string) $label);
        $base = trim(strtolower($safeUnit . '-' . $safeLabel), '-');
        $filename = $base . '.' . $ext;
        // ensure unique filename
        $candidate = $filename; $i = 1;
        try {
            while (Storage::disk($disk)->exists("$dir/$candidate") && $i < 100) {
                $candidate = $base . '-' . $i . '.' . $ext; $i++;
            }
            if ($i >= 100) { $candidate = $base . '-' . time() . '.' . $ext; }
        } catch (\Throwable $e) {
            \Log::error('Unhandled exception while checking document filename collisions', [
                'location' => __METHOD__,
                'class'    => static::class,
                'unit_id'  => $unit->id ?? null,
                'dir'      => $dir,
                'message'  => $e->getMessage(),
                'trace'    => $e->getTraceAsString(),
            ]);

            report($e);

            // fallback if exists() fails for any reason
            $candidate = $base . '-' . time() . '.' . $ext;
        }

        $stored = $file->storePubliclyAs($dir, $candidate, $disk);
        $publicUrl = Storage::disk($disk)->url($stored);

        // تحديد الكيان المرتبط
        $documentableType = Unit::class;
        $documentableId   = $unit->id;

        if ($data['attach_to'] === 'booking' && $unit->booking) {
            $documentableType = Booking::class;
            $documentableId   = $unit->booking->id;
        }

        Document::create([
            'name'              => $data['name'],
            'type'              => $data['type'],
            'path'              => $publicUrl,
            'documentable_type' => $documentableType,
            'documentable_id'   => $documentableId,
        ]);

        return back()->with('success', 'Document uploaded successfully.');
    }

    /**
     * Delete a document (unit/booking/customer) shown under a given unit.
     */
    public function destroy(Unit $unit, \App\Models\Document $document)
    {
        // Ensure the document belongs to this unit or its related booking/customer
        $allowed = false;
        $docType = $document->documentable_type;
        $docId   = $document->documentable_id;

        if ($docType === Unit::class && $docId == $unit->id) {
            $allowed = true;
        } elseif ($docType === \App\Models\Booking::class && $unit->booking && $docId == $unit->booking->id) {
            $allowed = true;
        } elseif ($docType === \App\Models\Customer::class && $unit->booking && $unit->booking->customer && $docId == $unit->booking->customer->id) {
            $allowed = true;
        }

        if (!$allowed) {
            abort(403, 'This document does not belong to this unit.');
        }

        // Best-effort: delete the file from known disks when possible
        try {
            $path = $document->path;
            $disks = [];
            if (config('filesystems.disks.s3')) $disks[] = 's3';
            $disks[] = 'public';
            foreach ($disks as $disk) {
                try {
                    $base = rtrim(\Illuminate\Support\Facades\Storage::disk($disk)->url('/'), '/');
                    if (is_string($path) && (str_starts_with($path, 'http://') || str_starts_with($path, 'https://'))) {
                        if (str_starts_with($path, $base)) {
                            $rel = ltrim(substr($path, strlen($base)), '/');
                            \Illuminate\Support\Facades\Storage::disk($disk)->delete($rel);
                        }
                    } else {
                        // relative path assumed for this disk
                        if (\Illuminate\Support\Facades\Storage::disk($disk)->exists($path)) {
                            \Illuminate\Support\Facades\Storage::disk($disk)->delete($path);
                        }
                    }
                } catch (\Throwable $e) {
                    \Log::error('Unhandled exception while deleting document file from disk', [
                        'location'    => __METHOD__,
                        'class'       => static::class,
                        'unit_id'     => $unit->id ?? null,
                        'document_id' => $document->id ?? null,
                        'disk'        => $disk,
                        'path'        => $path,
                        'message'     => $e->getMessage(),
                        'trace'       => $e->getTraceAsString(),
                    ]);

                    report($e);
                }
            }
        } catch (\Throwable $e) {
            \Log::error('Unhandled exception while attempting to delete document file', [
                'location'    => __METHOD__,
                'class'       => static::class,
                'unit_id'     => $unit->id ?? null,
                'document_id' => $document->id ?? null,
                'message'     => $e->getMessage(),
                'trace'       => $e->getTraceAsString(),
            ]);

            report($e);
        }

        $document->delete();

        return back()->with('success', 'Document deleted successfully.');
    }
}
