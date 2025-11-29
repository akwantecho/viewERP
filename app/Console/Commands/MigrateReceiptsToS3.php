<?php

namespace App\Console\Commands;

use App\Models\Payment;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class MigrateReceiptsToS3 extends Command
{
    protected $signature = 'receipts:migrate-to-s3 {--dry-run} {--limit=0}';
    protected $description = 'Copy existing payment receipt files from public disk to S3 and keep relative paths';

    public function handle(): int
    {
        if (! config('filesystems.disks.s3.bucket')) {
            $this->error('S3 disk is not configured (AWS_BUCKET missing).');
            return self::FAILURE;
        }

        $dry = (bool) $this->option('dry-run');
        $limit = (int) $this->option('limit');

        $query = Payment::query()
            ->whereNotNull('receipt')
            ->where('receipt', '!=', '');

        $total = (clone $query)->count();
        $this->info("Found {$total} payments with receipts.");

        $migrated = 0; $skipped = 0; $missing = 0; $already = 0; $errors = 0;

        $query->orderBy('id')->chunk(200, function ($payments) use (&$migrated, &$skipped, &$missing, &$already, &$errors, $dry, $limit) {
            foreach ($payments as $payment) {
                if ($limit && ($migrated + $skipped + $already + $missing + $errors) >= $limit) {
                    return false; // stop chunking
                }

                $path = $payment->receipt;

                // If already present on S3, skip
                try {
                    if (Storage::disk('s3')->exists($path)) {
                        $already++;
                        $this->line("[already] #{$payment->id} {$path}");
                        continue;
                    }
                } catch (\Throwable $e) {
                    $this->warn('S3 check failed: '.$e->getMessage());
                }

                // Try to read from public disk
                if (! Storage::disk('public')->exists($path)) {
                    $missing++;
                    $this->warn("[missing] #{$payment->id} not found on public: {$path}");
                    continue;
                }

                if ($dry) {
                    $skipped++;
                    $this->line("[dry-run] would copy {$path} to s3");
                    continue;
                }

                try {
                    $bytes = Storage::disk('public')->get($path);
                    // put with public visibility (disk configured as public too)
                    Storage::disk('s3')->put($path, $bytes, 'public');
                    $migrated++;
                    $this->info("[migrated] #{$payment->id} {$path}");
                } catch (\Throwable $e) {
                    $errors++;
                    $this->error("[error] #{$payment->id} {$path} => {$e->getMessage()}");
                }
            }
        });

        $this->newLine();
        $this->info("Done. migrated={$migrated}, already={$already}, dry_skipped={$skipped}, missing={$missing}, errors={$errors}");

        return self::SUCCESS;
    }
}

