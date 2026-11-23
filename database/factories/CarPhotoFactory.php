<?php

namespace Database\Factories;

use App\Models\CarPhoto;
use App\Models\Car;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CarPhoto>
 */
class CarPhotoFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'car_id' => Car::factory(),
            'path_foto' => 'mobil-fotos/'.fake()->uuid().'.jpg',
            'is_utama' => false,
        ];
    }
}
