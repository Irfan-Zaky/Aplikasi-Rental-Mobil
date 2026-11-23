<?php

use App\Enums\RentalStatus;
use App\Models\Mobil;
use App\Models\Rental;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('shows the availability screen and saves the draft when the car is free', function () {
    $this->travelTo('2026-11-23 09:00:00');

    $customer = User::factory()->create();
    $mobil = Mobil::factory()->create([
        'merk' => 'Toyota',
        'nama_mobil' => 'Avanza',
        'status' => 'Tersedia',
    ]);

    $this->actingAs($customer)
        ->get(route('customer.ketersediaan-mobil', [
            'id' => $mobil->id,
            'tanggal-mulai' => '2026-11-26',
            'tanggal-selesai' => '2026-11-27',
        ]))
        ->assertOk()
        ->assertSee('Mobil Tersedia')
        ->assertSessionHas('pemesanan_draft.mobil_id', $mobil->id);
});

it('opens the S-01 modal and leaves the draft empty when the car is already booked', function () {
    $this->travelTo('2026-11-23 09:00:00');

    $customer = User::factory()->create();
    $mobil = Mobil::factory()->create([
        'merk' => 'Toyota',
        'nama_mobil' => 'Avanza',
        'status' => 'Tersedia',
    ]);

    Rental::factory()->for($mobil, 'mobil')->create([
        'status' => RentalStatus::PembayaranBerhasil,
        'start_date' => '2026-11-23 00:00:00',
        'end_date' => '2026-11-25 23:59:59',
        'expires_at' => null,
    ]);

    $query = [
        'id' => $mobil->id,
        'tanggal-mulai' => '2026-11-23',
        'tanggal-selesai' => '2026-11-23',
    ];

    $this->actingAs($customer)
        ->get(route('customer.ketersediaan-mobil', $query))
        ->assertRedirect(route('customer.pilih-tanggal', $query))
        ->assertSessionHas('modal_tidak_tersedia.nama_mobil', 'Toyota Avanza')
        ->assertSessionMissing('pemesanan_draft.mobil_id');

    $this->get(route('customer.pilih-tanggal', $query))
        ->assertOk()
        ->assertSee('Mobil Tidak Tersedia')
        ->assertSee('Ganti Tanggal')
        ->assertSee('Ganti Mobil');
});

it('shows validation errors on the date form when the end date precedes the start date', function () {
    $this->travelTo('2026-11-23 09:00:00');

    $customer = User::factory()->create();
    $mobil = Mobil::factory()->create(['status' => 'Tersedia']);

    $urlPilihTanggal = route('customer.pilih-tanggal', ['id' => $mobil->id]);

    $this->from($urlPilihTanggal)
        ->actingAs($customer)
        ->followingRedirects()
        ->get(route('customer.ketersediaan-mobil', [
            'id' => $mobil->id,
            'tanggal-mulai' => '2026-11-24',
            'tanggal-selesai' => '2026-11-23',
        ]))
        ->assertOk()
        ->assertSee('The tanggal-selesai field must be a date after or equal to tanggal-mulai.');
});
