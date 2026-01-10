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
        try {
            // Comprehensive validation with security checks
            $request->validate([
                'file' => [
                    'required',
                    'file',
                    'max:20480', // 20MB max
                    'mimes:jpg,jpeg,png,gif,webp,svg',
                    'dimensions:max_width=8192,max_height=8192', // Prevent extremely large images
                ],
            ], [
                'file.mimes' => 'Only image files (JPG, PNG, GIF, WebP, SVG) are allowed.',
                'file.max' => 'File size must not exceed 20MB.',
                'file.dimensions' => 'Image dimensions must not exceed 8192x8192 pixels.',
            ]);

            $file = $request->file('file');

            // Use the configured default disk (S3) for consistency
            $disk = config('filesystems.default', 'public');

            // Organize by customer with safe naming
            $safeName = preg_replace('/[^A-Za-z0-9\-_.]+/', '-', strtoupper($customer->name));
            $directory = "customers/{$safeName}/notes";

            $extension = $file->getClientOriginalExtension();
            $baseFilename = Str::slug(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME));
            $filename = ($baseFilename ?: 'upload') . '-' . time() . '-' . Str::random(8);

            if ($extension) {
                $filename .= '.' . strtolower($extension);
            }

            // Store the file
            $storedPath = $file->storeAs($directory, $filename, $disk);

            if (!$storedPath) {
                throw new \Exception('Failed to store file on disk.');
            }

            // Get the full URL
            $absoluteUrl = Storage::disk($disk)->url($storedPath);

            // Record in database
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

            \Log::info('Customer note file uploaded successfully', [
                'customer_id' => $customer->id,
                'filename' => $filename,
                'size' => $file->getSize(),
                'disk' => $disk,
            ]);

            return response()->json(['location' => $absoluteUrl]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            // Re-throw validation exceptions to show proper error messages
            throw $e;
        } catch (\Exception $e) {
            \Log::error('Customer note upload failed', [
                'customer_id' => $customer->id,
                'user_id' => Auth::id(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'error' => 'Upload failed. Please try again or contact support if the problem persists.'
            ], 500);
        }
    }
}

