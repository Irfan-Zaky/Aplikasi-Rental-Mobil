<?php

namespace Database\Factories;

use App\Models\Car;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Car>
 */
class CarFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'kode_mobil' => 'MOB-'.fake()->unique()->numerify('########'),
            'merk' => fake()->randomElement(['Toyota', 'Daihatsu', 'Honda']),
            'nama_mobil' => fake()->randomElement(['Avanza', 'Xenia', 'Brio', 'BR-V']),
            'jenis' => fake()->randomElement(['MPV', 'SUV', 'Hatchback', 'Sedan']),
            'status' => fake()->randomElement(['Tersedia', 'Disewa', 'Perbaikan', 'Nonaktif']),
            'harga_sewa' => fake()->numberBetween(300000, 900000),
            'nomor_polisi' => 'B '.fake()->unique()->numerify('#### ABC'),
            'spesifikasi' => fake()->sentence(),
        ];
    }
}
