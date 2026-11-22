<?php

namespace Database\Factories;

use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\PaymentType;
use App\Models\Payment;
use App\Models\Rental;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Payment>
 */
class PaymentFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'rental_id' => Rental::factory(),
            'type' => PaymentType::Sewa,
            'method' => PaymentMethod::Tunai,
            'amount' => 1000000,
            'status' => PaymentStatus::MenungguPembayaran,
            'expires_at' => now()->addHours(3),
        ];
    }
}
