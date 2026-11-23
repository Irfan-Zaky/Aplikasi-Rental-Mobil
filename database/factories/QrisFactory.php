<?php

namespace Database\Factories;

use App\Enums\PaymentMethod;
use App\Models\Payment;
use App\Models\Qris;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Qris>
 */
class QrisFactory extends Factory
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
