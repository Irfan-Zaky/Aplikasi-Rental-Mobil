<?php

return [
    'driver_fee_per_day' => (int) env('RENTAL_DRIVER_FEE_PER_DAY', 75000),
    'contact_phone' => env('RENTAL_CONTACT_PHONE', '081234567890'),
    'pending_ttl_minutes' => (int) env('RENTAL_PENDING_TTL_MINUTES', 30),
    'qris_ttl_minutes' => (int) env('RENTAL_QRIS_TTL_MINUTES', 10),
    'cash_ttl_hours' => (int) env('RENTAL_CASH_TTL_HOURS', 3),
    'late_fee_rate_per_hour' => (float) env('RENTAL_LATE_FEE_RATE_PER_HOUR', 0.06),
];
