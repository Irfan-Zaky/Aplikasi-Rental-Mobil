<?php

namespace Database\Factories;

use App\Models\Cash;
use App\Models\Payment;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Cash>
 */
class CashFactory extends Factory
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
