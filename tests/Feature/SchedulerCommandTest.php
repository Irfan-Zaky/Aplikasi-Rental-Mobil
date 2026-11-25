<?php

use App\Enums\CarStatus;
use App\Enums\RentalStatus;
use App\Models\BookingHistory;
use App\Models\Car;
use App\Models\Rental;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('memulai sewa yang sudah tiba dan menandai yang terlambat', function (): void {
    $this->travelTo('2026-11-24 10:00:00');

    $mobilSewa = Car::factory()->create(['status' => CarStatus::Tersedia]);
    $sewaDimulai = Rental::factory()->create([
        'car_id' => $mobilSewa->car_id,
        'status' => RentalStatus::PembayaranBerhasil,
        'start_date' => now()->subMinute(),
    ]);

    $mobilTerlambat = Car::factory()->create(['status' => CarStatus::Disewa]);
    $sewaTerlambat = Rental::factory()->create([
        'car_id' => $mobilTerlambat->car_id,
        'status' => RentalStatus::Berjalan,
        'end_date' => now()->subMinute(),
    ]);

    $this->artisan('rentals:tick')->assertSuccessful();

    expect($sewaDimulai->fresh()->status)->toBe(RentalStatus::Berjalan)
        ->and($mobilSewa->fresh()->status)->toBe(CarStatus::Disewa->value)
        ->and($sewaTerlambat->fresh()->status)->toBe(RentalStatus::Terlambat)
        ->and(BookingHistory::whereIn('rental_id', [$sewaDimulai->rental_id, $sewaTerlambat->rental_id])->count())->toBe(2);
});

it('menghapus mobil nonaktif yang tidak punya rental pemblokir', function (): void {
    $mobilDihapus = Car::factory()->create(['status' => CarStatus::Nonaktif]);
    $mobilDitahan = Car::factory()->create(['status' => CarStatus::Nonaktif]);

    Rental::factory()->create([
        'car_id' => $mobilDitahan->car_id,
        'status' => RentalStatus::MenungguPembayaran,
        'expires_at' => now()->addHours(2),
    ]);

    $this->artisan('mobils:purge-inactive')->assertSuccessful();

    $this->assertSoftDeleted('car', ['car_id' => $mobilDihapus->car_id]);
    $this->assertDatabaseHas('car', [
        'car_id' => $mobilDitahan->car_id,
        'status' => CarStatus::Nonaktif->value,
        'deleted_at' => null,
    ]);
});
