<?php

declare(strict_types=1);

namespace App\DataTransferObjects;

use App\Models\BackupLog;

/**
 * Immutable data structure describing the outcome of a backup attempt.
 */
final class BackupOutcome
{
    public function __construct(
        public readonly BackupLog $log,
        public readonly string $localPath,
        public readonly float $runtimeSeconds,
        public readonly ?string $fileHash = null,
        public readonly ?string $remoteDisk = null,
        public readonly ?string $remotePath = null,
        public readonly ?string $cloudUrl = null,
        public readonly ?string $errorTrace = null
    ) {
    }

    public function wasSuccessful(): bool
    {
        return $this->log->status === 'success';
    }

    public function hasRemoteCopy(): bool
    {
        return $this->remoteDisk !== null && $this->remotePath !== null && $this->wasSuccessful();
    }
}
