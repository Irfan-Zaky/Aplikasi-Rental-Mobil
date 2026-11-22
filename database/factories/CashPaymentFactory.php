<?php

namespace Database\Factories;

use App\Models\CashPayment;
use App\Models\Payment;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CashPayment>
 */
class CashPaymentFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'payment_id' => Payment::factory(),
            'payment_code' => 'TNI-'.fake()->unique()->bothify('????-????'),
        ];
    }
}
