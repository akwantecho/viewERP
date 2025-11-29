<?php

namespace App\Http\Controllers\Concerns;

use Illuminate\Http\Request;

trait NormalizesReferenceInput
{
    protected function normalizeReferenceInput(Request $request, string $field = 'reference_no'): ?string
    {
        $raw = $request->input($field);

        if (is_array($raw)) {
            $filtered = [];
            foreach ($raw as $value) {
                if ($value !== null && $value !== '') {
                    $filtered[] = $value;
                }
            }
            $raw = $filtered[0] ?? null;
        }

        if (is_scalar($raw)) {
            $normalized = trim((string) $raw);

            return $normalized === '' ? null : $normalized;
        }

        return null;
    }
}

