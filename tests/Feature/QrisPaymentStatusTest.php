<?php

use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\PaymentType;
use App\Enums\RentalStatus;
use App\Models\Account;
use App\Models\Payment;
use App\Models\Qris;
use App\Models\Rental;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;

uses(RefreshDatabase::class);

/**
 * @return array{rental: Rental, payment: Payment}
 */
function fixtureStatusQris(PaymentStatus $status, RentalStatus $rentalStatus): array
{
    $customer = Account::factory()->create();

    $rental = Rental::factory()->create([
        'account_id' => $customer->account_id,
        'status' => $rentalStatus,
    ]);

    $payment = Payment::factory()->for($rental, 'rental')->create([
        'type' => PaymentType::Sewa,
        'method' => PaymentMethod::QRIS,
        'status' => $status,
    ]);

    Qris::factory()->for($payment, 'payment')->create([
        'qr_code' => 'https://sandbox.midtrans.test/qr/example.png',
    ]);

    return ['customer' => $customer, 'rental' => $rental, 'payment' => $payment];
}

it('mengembalikan status menunggu untuk pembayaran QRIS yang belum dibayar', function (): void {
    $fixture = fixtureStatusQris(PaymentStatus::MenungguPembayaran, RentalStatus::MenungguPembayaran);

    $this->actingAs($fixture['customer'])
        ->withSession(['pemesanan_terakhir' => $fixture['rental']->rental_id])
        ->getJson(route('customer.pembayaran-qris.status'))
        ->assertOk()
        ->assertExactJson([
            'status' => 'Menunggu Pembayaran',
            'paid' => false,
        ]);
});

it('mengembalikan paid setelah webhook QRIS diterima', function (): void {
    $fixture = fixtureStatusQris(PaymentStatus::MenungguKonfirmasi, RentalStatus::MenungguVerifikasi);

    $this->actingAs($fixture['customer'])
        ->getJson(route('customer.pembayaran-qris.status', ['id' => $fixture['rental']->rental_id]))
        ->assertOk()
        ->assertExactJson([
            'status' => 'Menunggu Konfirmasi',
            'paid' => true,
        ]);
});

it('tidak mengembalikan status pembayaran rental milik customer lain', function (): void {
    $customer = Account::factory()->create();
    $rental = Rental::factory()->create();

    Payment::factory()->for($rental, 'rental')->create([
        'type' => PaymentType::Sewa,
        'method' => PaymentMethod::QRIS,
        'status' => PaymentStatus::MenungguKonfirmasi,
    ]);

    $this->actingAs($customer)
        ->getJson(route('customer.pembayaran-qris.status', ['id' => $rental->rental_id]))
        ->assertNotFound()
        ->assertJson(['message' => 'Transaksi tidak ditemukan.']);
});

it('menampilkan halaman sukses dan arah ke dashboard saat pembayaran QRIS diterima', function (): void {
    Http::preventStrayRequests();

    $fixture = fixtureStatusQris(PaymentStatus::MenungguKonfirmasi, RentalStatus::MenungguVerifikasi);

    $this->actingAs($fixture['customer'])
        ->withSession(['pemesanan_terakhir' => $fixture['rental']->rental_id])
        ->get(route('customer.pembayaran-qris'))
        ->assertOk()
        ->assertSee('Pembayaran diterima')
        ->assertSee('Ke Dashboard Sekarang')
        ->assertSee(route('customer.beranda'))
        ->assertDontSee('Scan dengan aplikasi pembayaran');
});

it('tetap menampilkan QR dan menyiapkan polling saat pembayaran masih menunggu', function (): void {
    Http::preventStrayRequests();

    $fixture = fixtureStatusQris(PaymentStatus::MenungguPembayaran, RentalStatus::MenungguPembayaran);
    $statusUrl = route('customer.pembayaran-qris.status', ['id' => $fixture['rental']->rental_id]);

    $this->actingAs($fixture['customer'])
        ->withSession(['pemesanan_terakhir' => $fixture['rental']->rental_id])
        ->get(route('customer.pembayaran-qris'))
        ->assertOk()
        ->assertSee('Scan dengan aplikasi pembayaran')
        ->assertSee($statusUrl)
        ->assertDontSee('Pembayaran diterima');
});
