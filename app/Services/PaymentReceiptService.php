<?php

namespace App\Services;

use App\Models\Booking;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Spatie\LaravelImageOptimizer\Facades\ImageOptimizer;

class PaymentReceiptService
{
    /**
     * Store a receipt file and optionally delete an old copy.
     */
    public function store(UploadedFile $file, Booking $booking, ?string $reference = null, ?string $oldPath = null): string
    {
        $disk = config('filesystems.disks.s3') ? 's3' : 'public';
        $ext = strtolower($file->getClientOriginalExtension() ?: 'bin');
        $base = $this->sanitizeBaseName($reference);

        $dir = $this->directoryFor($booking);
        $candidate = $this->uniqueFilename($disk, $dir, $base, $ext);

        try {
            ImageOptimizer::optimize($file->getRealPath());
        } catch (\Throwable $e) {
            \Log::error('Unhandled exception while optimizing payment receipt upload', [
                'location'   => __METHOD__,
                'class'      => static::class,
                'booking_id' => $booking->id ?? null,
                'message'    => $e->getMessage(),
                'trace'      => $e->getTraceAsString(),
            ]);

            report($e);
        }

        $path = $file->storePubliclyAs($dir, $candidate, $disk);

        if ($oldPath) {
            $this->delete($oldPath);
        }

        return $path;
    }

    /**
     * Delete a stored receipt if it exists.
     */
    public function delete(?string $path): void
    {
        if (!$path) {
            return;
        }

        // Try all configured disks where receipts might live.
        $disks = ['public'];
        if (config('filesystems.disks.s3')) {
            $disks[] = 's3';
        }

        foreach ($disks as $disk) {
            try {
                if (Storage::disk($disk)->exists($path)) {
                    Storage::disk($disk)->delete($path);
                    return;
                }
            } catch (\Throwable $e) {
                \Log::error('Unhandled exception while deleting payment receipt', [
                    'location' => __METHOD__,
                    'class'    => static::class,
                    'disk'     => $disk,
                    'path'     => $path,
                    'message'  => $e->getMessage(),
                    'trace'    => $e->getTraceAsString(),
                ]);

                report($e);
            }
        }
    }

    private function directoryFor(Booking $booking): string
    {
        $project = optional(optional(optional($booking->unit)->floor)->project);
        $projectCode = strtoupper((string) $project->code);
        $unitCode    = strtoupper((string) optional($booking->unit)->unit_code);
        $safeProject = $this->slug($projectCode ?: 'PROJECT');
        $safeUnit    = $this->slug($unitCode ?: ('UNIT-' . $booking->unit_id));

        return "bookings/{$safeProject}/{$safeUnit}/payments";
    }

    private function uniqueFilename(string $disk, string $dir, string $base, string $ext): string
    {
        $candidate = $base . '.' . $ext;
        $i = 1;

        try {
            while (Storage::disk($disk)->exists("{$dir}/{$candidate}") && $i < 50) {
                $candidate = $base . '-' . $i . '.' . $ext;
                $i++;
            }
        } catch (\Throwable $e) {
            \Log::error('Unhandled exception while checking for existing receipt filename', [
                'location' => __METHOD__,
                'class'    => static::class,
                'disk'     => $disk,
                'dir'      => $dir,
                'message'  => $e->getMessage(),
                'trace'    => $e->getTraceAsString(),
            ]);

            report($e);
        }

        if ($i >= 50) {
            $candidate = $base . '-' . time() . '.' . $ext;
        }

        return $candidate;
    }

    private function sanitizeBaseName(?string $reference): string
    {
        $base = strtolower(preg_replace('/[^A-Za-z0-9\-_.]+/', '-', (string) $reference));

        return $base ?: 'receipt';
    }

    private function slug(string $value): string
    {
        $slug = preg_replace('/[^A-Za-z0-9\-_.]+/', '-', $value);

        return $slug ?: 'NA';
    }
}
