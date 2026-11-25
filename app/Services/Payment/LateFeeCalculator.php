<?php

declare(strict_types=1);

namespace App\Services\Payment;

use Carbon\Carbon;

final class LateFeeCalculator
{
    public function hitung(Carbon $endDate, int $pricePerDay, ?Carbon $now = null): int
    {
        $now ??= now();

        if ($now->lessThanOrEqualTo($endDate)) {
            return 0;
        }

        $menit = (int) ceil($endDate->diffInMinutes($now));
        $jam = max(1, (int) ceil($menit / 60));
        $tarif = (float) config('rental.late_fee_rate_per_hour');

        return (int) ceil($jam * $tarif * $pricePerDay);
    }
}
