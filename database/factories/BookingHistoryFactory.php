<?php

namespace Database\Factories;

use App\Enums\RentalStatus;
use App\Models\BookingHistory;
use App\Models\Rental;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BookingHistory>
 */
class BookingHistoryFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'rental_id' => Rental::factory(),
            'sequence_no' => 1,
            'status' => RentalStatus::MenungguPembayaran,
            'changed_at' => now(),
        ];
    }
}
