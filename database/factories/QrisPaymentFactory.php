<?php

namespace Database\Factories;

use App\Enums\PaymentMethod;
use App\Models\Payment;
use App\Models\QrisPayment;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<QrisPayment>
 */
class QrisPaymentFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'payment_id' => Payment::factory()->state(['method' => PaymentMethod::QRIS]),
            'qr_ref' => 'QR-'.fake()->unique()->numerify('########'),
            'qr_code' => fake()->url(),
        ];
    }
}
