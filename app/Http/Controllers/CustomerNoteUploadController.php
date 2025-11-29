<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\CustomerNoteFile;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class CustomerNoteUploadController extends Controller
{
    public function store(Request $request, Customer $customer): JsonResponse
    {
        $request->validate([
            'file' => ['required', 'file', 'max:20480'],
        ]);

        $file = $request->file('file');
        $disk = 'public';
        $directory = 'customer-notes/' . $customer->id;
        $extension = $file->getClientOriginalExtension();
        $safeName = Str::slug(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME));
        $filename = $safeName ?: 'upload';
        $filename .= '-' . Str::random(8);
        if ($extension) {
            $filename .= '.' . strtolower($extension);
        }

        $storedPath = $file->storeAs($directory, $filename, $disk);
        $absoluteUrl = Storage::disk($disk)->url($storedPath);

        CustomerNoteFile::create([
            'customer_id' => $customer->id,
            'customer_note_id' => null,
            'user_id' => Auth::id(),
            'path' => $storedPath,
            'disk' => $disk,
            'original_name' => $file->getClientOriginalName(),
            'mime_type' => $file->getClientMimeType(),
            'size' => $file->getSize(),
        ]);

        return response()->json(['location' => $absoluteUrl]);
    }
}

