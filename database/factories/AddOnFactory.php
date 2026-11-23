<?php

namespace Database\Factories;

use App\Enums\HandoverMethod;
use App\Models\Rental;
use App\Models\AddOn;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AddOn>
 */
class AddOnFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'rental_id' => Rental::factory(),
            'handover_method' => HandoverMethod::AmbilDiGarasi,
            'pickup_point' => null,
            'driver_fee' => 0,
        ];
    }
}
