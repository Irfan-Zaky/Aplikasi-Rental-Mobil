<?php

use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\PaymentType;
use App\Enums\RentalStatus;
use App\Models\BookingHistory;
use App\Models\Payment;
use App\Models\Rental;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('cancels an unpaid rental when its cash payment expires', function () {
    $this->travelTo('2026-11-24 10:00:00');

    $rental = Rental::factory()->create([
        'status' => RentalStatus::MenungguPembayaran,
        'expires_at' => now()->addHours(3),
    ]);
    BookingHistory::factory()->for($rental, 'rental')->create([
        'sequence_no' => 1,
        'status' => RentalStatus::MenungguPembayaran,
    ]);
    $payment = Payment::factory()->for($rental, 'rental')->create([
        'type' => PaymentType::Sewa,
        'method' => PaymentMethod::Tunai,
        'status' => PaymentStatus::MenungguPembayaran,
        'expires_at' => now()->subMinute(),
    ]);

    $this->artisan('rentals:expire')->assertSuccessful();

    expect($rental->fresh()->status)->toBe(RentalStatus::Dibatalkan);
    expect($payment->fresh()->status)->toBe(PaymentStatus::Kadaluarsa);
    expect($rental->fresh()->cancelled_at)->not->toBeNull();

    $this->assertDatabaseHas('booking_history', [
        'rental_id' => $rental->rental_id,
        'sequence_no' => 2,
        'status' => RentalStatus::Dibatalkan->value,
        'changed_by' => null,
        'note' => 'Dibatalkan otomatis: batas pembayaran terlewat',
    ]);
});

it('does not cancel a rental whose payment is no longer unpaid', function (PaymentStatus $status) {
    $this->travelTo('2026-11-24 10:00:00');

    $rental = Rental::factory()->create([
        'status' => RentalStatus::MenungguVerifikasi,
        'expires_at' => now()->addHours(3),
    ]);
    $payment = Payment::factory()->for($rental, 'rental')->create([
        'type' => PaymentType::Sewa,
        'method' => PaymentMethod::QRIS,
        'status' => $status,
        'expires_at' => now()->subMinute(),
    ]);

    $this->artisan('rentals:expire')->assertSuccessful();

    expect($rental->fresh()->status)->toBe(RentalStatus::MenungguVerifikasi);
    expect($payment->fresh()->status)->toBe($status);
    expect($rental->fresh()->cancelled_at)->toBeNull();
})->with([
    'awaiting admin confirmation' => [PaymentStatus::MenungguKonfirmasi],
    'already successful' => [PaymentStatus::Berhasil],
]);

it('leaves an unpaid rental alone before its payment expires', function () {
    $this->travelTo('2026-11-24 10:00:00');

    $rental = Rental::factory()->create([
        'status' => RentalStatus::MenungguPembayaran,
        'expires_at' => now()->addHours(3),
    ]);
    $payment = Payment::factory()->for($rental, 'rental')->create([
        'type' => PaymentType::Sewa,
        'method' => PaymentMethod::Tunai,
        'status' => PaymentStatus::MenungguPembayaran,
        'expires_at' => now()->addMinute(),
    ]);

    $this->artisan('rentals:expire')->assertSuccessful();

    expect($rental->fresh()->status)->toBe(RentalStatus::MenungguPembayaran);
    expect($payment->fresh()->status)->toBe(PaymentStatus::MenungguPembayaran);
});

it('does not append another history row when run twice', function () {
    $this->travelTo('2026-11-24 10:00:00');

    $rental = Rental::factory()->create([
        'status' => RentalStatus::MenungguPembayaran,
        'expires_at' => now()->addHours(3),
    ]);
    BookingHistory::factory()->for($rental, 'rental')->create([
        'sequence_no' => 1,
        'status' => RentalStatus::MenungguPembayaran,
    ]);
    Payment::factory()->for($rental, 'rental')->create([
        'type' => PaymentType::Sewa,
        'method' => PaymentMethod::Tunai,
        'status' => PaymentStatus::MenungguPembayaran,
        'expires_at' => now()->subMinute(),
    ]);

    $this->artisan('rentals:expire')->assertSuccessful();
    $this->artisan('rentals:expire')->assertSuccessful();

    $this->assertDatabaseCount('booking_history', 2);
});
