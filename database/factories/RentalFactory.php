<?php

namespace Database\Factories;

use App\Enums\RentalStatus;
use App\Models\Account;
use App\Models\Car;
use App\Models\Rental;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Rental>
 */
class RentalFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'trx_code' => 'TRX-'.fake()->unique()->numerify('########'),
            'account_id' => Account::factory(),
            'car_id' => Car::factory(),
            'start_date' => now()->addDay()->startOfDay(),
            'end_date' => now()->addDays(2)->endOfDay(),
            'duration' => 2,
            'price_per_day' => 500000,
            'subtotal' => 1000000,
            'total_rental' => 1000000,
            'late_fee' => 0,
            'self_driver' => true,
            'customer_name' => fake()->name(),
            'customer_nik' => fake()->numerify('################'),
            'customer_phone' => fake()->numerify('08##########'),
            'customer_ktp_photo' => 'verifikasi/ktp/ktp.jpg',
            'customer_selfie_photo' => 'verifikasi/selfie/selfie.jpg',
            'terms_accepted_at' => now(),
            'status' => RentalStatus::MenungguPembayaran,
            'expires_at' => now()->addMinutes(30),
        ];
    }
}
