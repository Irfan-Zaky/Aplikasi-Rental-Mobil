<?php

namespace Database\Factories;

use App\Models\FotoMobil;
use App\Models\Mobil;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<FotoMobil>
 */
class FotoMobilFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'mobil_id' => Mobil::factory(),
            'path_foto' => 'mobil-fotos/'.fake()->uuid().'.jpg',
            'is_utama' => false,
        ];
    }
}
