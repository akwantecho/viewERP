<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\Installment;
use Carbon\Carbon;

class InstallmentService
{
    /**
     * Generate installment plan for a booking (excluding advance payment).
     *
     * Supported modes:
     *  A) Fixed: split remaining amount equally across N installments.
     *  B) Custom: caller provides explicit number/due_date/total rows.
     *  C) Legacy: keeps the historic behaviour that relied on a fixed
     *             per-installment value and balloon adjustments.
     */
    public function generatePlan(Booking $booking, array $options): void
    {
        $remaining = round((float) ($options['remaining'] ?? 0), 2);
        if ($remaining <= 0) {
            return;
        }

        $mode = $this->determineMode($options);

        match ($mode) {
            'custom' => $this->generateCustomPlan($booking, $remaining, $options),
            'legacy' => $this->generateLegacyPlan($booking, $remaining, $options),
            default  => $this->generateFixedPlan($booking, $remaining, $options),
        };
    }

    protected function determineMode(array $options): string
    {
        if (!empty($options['custom_installments']) && is_array($options['custom_installments'])) {
            return 'custom';
        }

        if (!empty($options['legacy']) || (($options['installment_value'] ?? 0) > 0)) {
            return 'legacy';
        }

        return 'fixed';
    }

    protected function generateFixedPlan(Booking $booking, float $remaining, array $options): void
    {
        $count = max(0, (int) ($options['installments_count'] ?? 0));
        if ($count <= 0) {
            return;
        }

        $vatRate   = (float) config('app.vat_rate', 0.05);
        $startDate = $this->resolveDate($options['start_date'] ?? now());
        $frequency = max(1, (int) ($options['installment_frequency'] ?? 1));
        $dueDay    = max(1, (int) ($options['monthly_due_day'] ?? $startDate->day));

        $baseMonth = $startDate->copy()->startOfMonth();
        $totals    = [];
        $perTotal  = round($remaining / $count, 2);

        for ($i = 1; $i <= $count; $i++) {
            $total = $perTotal;
            if ($i === $count) {
                $assigned = round(array_sum($totals), 2);
                $total = round($remaining - $assigned, 2);
            }

            $totals[] = $total;
            [$amount, $vat] = $this->splitAmounts($total, $vatRate);

            $dueDate = $this->calculateDueDate($baseMonth, $frequency, $dueDay, $i);

            Installment::create([
                'booking_id'        => $booking->id,
                'installment_number'=> $i,
                'due_date'          => $dueDate,
                'amount'            => $amount,
                'vat'               => $vat,
                'total_amount'      => $total,
                'status'            => 'unpaid',
                'remaining_before'  => null,
                'remaining_after'   => null,
            ]);
        }
    }

    protected function generateCustomPlan(Booking $booking, float $remaining, array $options): void
    {
        $rows = array_values($options['custom_installments'] ?? []);
        if (empty($rows)) {
            return;
        }

        $vatRate = (float) config('app.vat_rate', 0.05);
        $totals  = [];
        $parsed  = [];

        foreach ($rows as $index => $row) {
            $total = round((float) ($row['total'] ?? $row['total_amount'] ?? 0), 2);
            if ($total <= 0) {
                continue;
            }

            $dueDate = $this->resolveDate($row['due_date'] ?? now());
            $number  = (int) ($row['number'] ?? ($index + 1));

            $parsed[] = [
                'number'  => $number,
                'dueDate' => $dueDate,
                'total'   => $total,
            ];
            $totals[] = $total;
        }

        if (empty($parsed)) {
            return;
        }

        $sum   = round(array_sum($totals), 2);
        $diff  = round($remaining - $sum, 2);
        $tolerance = 0.05;
        if (abs($diff) > $tolerance) {
            throw new \InvalidArgumentException('Custom installments totals do not match remaining amount.');
        }

        if (abs($diff) >= 0.01) {
            $lastIndex = array_key_last($parsed);
            $parsed[$lastIndex]['total'] = round($parsed[$lastIndex]['total'] + $diff, 2);
        }

        foreach ($parsed as $row) {
            [$amount, $vat] = $this->splitAmounts($row['total'], $vatRate);

            Installment::create([
                'booking_id'        => $booking->id,
                'installment_number'=> $row['number'],
                'due_date'          => $row['dueDate'],
                'amount'            => $amount,
                'vat'               => $vat,
                'total_amount'      => $row['total'],
                'status'            => 'unpaid',
                'remaining_before'  => null,
                'remaining_after'   => null,
            ]);
        }
    }

    protected function generateLegacyPlan(Booking $booking, float $remaining, array $options): void
    {
        $n = (int) ($options['installments_count'] ?? 0);
        if ($n <= 0) {
            return;
        }

        $vatRate   = (float) config('app.vat_rate', 0.05);
        $frequency = max(1, (int) ($options['installment_frequency'] ?? 1));
        $startDate = $this->resolveDate($options['start_date'] ?? now());
        $dueDay    = max(1, (int) ($options['monthly_due_day'] ?? $startDate->day));

        $baseMonth = $startDate->copy()->startOfMonth();
        $perTotal  = isset($options['installment_value'])
            ? round((float) $options['installment_value'], 2)
            : round($remaining / $n, 2);
        $sumTotals = 0.0;

        for ($i = 1; $i <= $n; $i++) {
            $dueDate = $this->calculateDueDate($baseMonth, $frequency, $dueDay, $i);
            $total   = round($perTotal, 2);
            [$amount, $vat] = $this->splitAmounts($total, $vatRate);

            Installment::create([
                'booking_id'        => $booking->id,
                'installment_number'=> $i,
                'due_date'          => $dueDate,
                'amount'            => $amount,
                'vat'               => $vat,
                'total_amount'      => $total,
                'status'            => 'unpaid',
                'remaining_before'  => null,
                'remaining_after'   => null,
            ]);

            $sumTotals = round($sumTotals + $total, 2);
        }

        $balloon = round($remaining - $sumTotals, 2);
        if ($balloon > 0) {
            [$amount, $vat] = $this->splitAmounts($balloon, $vatRate);

            Installment::create([
                'booking_id'        => $booking->id,
                'installment_number'=> $n + 1,
                'due_date'          => $this->calculateDueDate($baseMonth, $frequency, $dueDay, $n + 1),
                'amount'            => $amount,
                'vat'               => $vat,
                'total_amount'      => $balloon,
                'status'            => 'unpaid',
                'remaining_before'  => null,
                'remaining_after'   => null,
            ]);
        }
    }

    protected function resolveDate($value): Carbon
    {
        if ($value instanceof Carbon) {
            return $value->copy()->startOfDay();
        }

        return Carbon::parse($value)->startOfDay();
    }

    protected function calculateDueDate(Carbon $baseMonth, int $frequency, int $dueDay, int $step): Carbon
    {
        $targetMonth = $baseMonth->copy()->addMonthsNoOverflow($frequency * $step);
        $day = min($dueDay, $targetMonth->daysInMonth);

        return $targetMonth->copy()->day($day);
    }

    protected function splitAmounts(float $total, float $vatRate): array
    {
        $total = round($total, 2);
        $vat   = round($total * $vatRate, 2);
        $base  = round($total - $vat, 2);

        return [$base, $vat];
    }
}
