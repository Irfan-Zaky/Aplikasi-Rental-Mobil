<?php

namespace Database\Factories;

use App\Enums\HandoverMethod;
use App\Models\Rental;
use App\Models\RentalAddOn;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<RentalAddOn>
 */
class RentalAddOnFactory extends Factory
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
