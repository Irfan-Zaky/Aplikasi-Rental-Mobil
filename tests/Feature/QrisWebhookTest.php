<?php

use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\PaymentType;
use App\Enums\RentalStatus;
use App\Models\BookingHistory;
use App\Models\Payment;
use App\Models\Qris;
use App\Models\Rental;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;

uses(RefreshDatabase::class);

/**
 * @return array{rental: Rental, payment: Payment, qris: Qris}
 */
function fixtureWebhookQris(): array
{
    $rental = Rental::factory()->create([
        'status' => RentalStatus::MenungguPembayaran,
        'total_rental' => 750000,
    ]);

    BookingHistory::factory()->for($rental, 'rental')->create([
        'sequence_no' => 1,
        'status' => RentalStatus::MenungguPembayaran,
    ]);

    $payment = Payment::factory()->for($rental, 'rental')->create([
        'type' => PaymentType::Sewa,
        'method' => PaymentMethod::QRIS,
        'amount' => 750000,
        'status' => PaymentStatus::MenungguPembayaran,
    ]);

    $qris = Qris::factory()->for($payment, 'payment')->create([
        'qr_ref' => 'QR-WEBHOOK-0001',
    ]);

    return ['rental' => $rental, 'payment' => $payment, 'qris' => $qris];
}

/**
 * @return array<string, string>
 */
function payloadWebhookQris(string $orderId, string $transactionStatus, ?string $secret = null): array
{
    $statusCode = '00';
    $grossAmount = '750000';
    $secret ??= (string) config('midtrans.signature_secret_key');

    return [
        'order_id' => $orderId,
        'status_code' => $statusCode,
        'gross_amount' => $grossAmount,
        'transaction_status' => $transactionStatus,
        'signature_key' => hash('sha512', $orderId.$statusCode.$grossAmount.$secret),
    ];
}

beforeEach(function (): void {
    config()->set('midtrans.server_key', 'server-key-test');
    config()->set('midtrans.signature_secret_key', 'signature-secret-key-test');
    Http::preventStrayRequests();
});

it('menolak webhook dengan signature tidak valid', function (): void {
    $fixture = fixtureWebhookQris();
    $payload = payloadWebhookQris($fixture['qris']->qr_ref, 'settlement');
    $payload['signature_key'] = str_repeat('0', 128);

    $this->postJson(route('webhooks.qris'), $payload)->assertForbidden();

    expect($fixture['payment']->fresh()->status)->toBe(PaymentStatus::MenungguPembayaran)
        ->and($fixture['rental']->fresh()->status)->toBe(RentalStatus::MenungguPembayaran);
});

it('mengembalikan 403 untuk webhook yang ditandatangani dengan server key', function (): void {
    $fixture = fixtureWebhookQris();
    $payload = payloadWebhookQris(
        $fixture['qris']->qr_ref,
        'settlement',
        (string) config('midtrans.server_key'),
    );

    $this->postJson(route('webhooks.qris'), $payload)->assertForbidden();

    expect($fixture['payment']->fresh()->status)->toBe(PaymentStatus::MenungguPembayaran)
        ->and($fixture['rental']->fresh()->status)->toBe(RentalStatus::MenungguPembayaran);
});

it('mengembalikan 502 dan tidak mengubah status saat Get Status Midtrans tidak valid', function (): void {
    $fixture = fixtureWebhookQris();

    Http::fake([
        '*/v2/QR-WEBHOOK-0001/status' => Http::response([
            'status_code' => '404',
            'status_message' => "Transaction doesn't exist.",
        ]),
    ]);

    $this->postJson(route('webhooks.qris'), payloadWebhookQris($fixture['qris']->qr_ref, 'settlement'))
        ->assertStatus(502)
        ->assertJson(['message' => 'Gagal memverifikasi status pembayaran.']);

    expect($fixture['payment']->fresh()->status)->toBe(PaymentStatus::MenungguPembayaran)
        ->and($fixture['rental']->fresh()->status)->toBe(RentalStatus::MenungguPembayaran)
        ->and(BookingHistory::where('rental_id', $fixture['rental']->rental_id)->count())->toBe(1);
});

it('membalas 200 untuk Get Status pending dengan status code 201', function (): void {
    $fixture = fixtureWebhookQris();

    Http::fake([
        '*/v2/QR-WEBHOOK-0001/status' => Http::response([
            'status_code' => '201',
            'status_message' => 'Success, transaction is found',
            'transaction_status' => 'pending',
        ]),
    ]);

    $this->postJson(route('webhooks.qris'), payloadWebhookQris($fixture['qris']->qr_ref, 'pending'))
        ->assertOk()
        ->assertJson(['status' => 'ok']);

    expect($fixture['payment']->fresh()->status)->toBe(PaymentStatus::MenungguPembayaran)
        ->and($fixture['rental']->fresh()->status)->toBe(RentalStatus::MenungguPembayaran)
        ->and($fixture['qris']->fresh()->raw_payload['transaction_status'])->toBe('pending')
        ->and(BookingHistory::where('rental_id', $fixture['rental']->rental_id)->count())->toBe(1);
});

it('menerima webhook settlement dan idempoten untuk duplikatnya', function (): void {
    $fixture = fixtureWebhookQris();

    Http::fake([
        '*/v2/QR-WEBHOOK-0001/status' => Http::response([
            'order_id' => 'QR-WEBHOOK-0001',
            'status_code' => '200',
            'gross_amount' => '750000',
            'transaction_status' => 'settlement',
        ]),
    ]);

    $payload = payloadWebhookQris($fixture['qris']->qr_ref, 'settlement');

    $this->postJson(route('webhooks.qris'), $payload)->assertOk()->assertJson(['status' => 'ok']);
    $this->postJson(route('webhooks.qris'), $payload)->assertOk();

    $payment = $fixture['payment']->fresh();
    $rental = $fixture['rental']->fresh();

    expect($payment->status)->toBe(PaymentStatus::MenungguKonfirmasi)
        ->and($payment->paid_at)->toBeNull()
        ->and($rental->status)->toBe(RentalStatus::MenungguVerifikasi)
        ->and($rental->booking_code)->toBeNull()
        ->and($rental->invoice_number)->toBeNull()
        ->and($fixture['qris']->fresh()->raw_payload['transaction_status'])->toBe('settlement')
        ->and(BookingHistory::where('rental_id', $rental->rental_id)->count())->toBe(2);
});

it('membatalkan pembayaran dan rental saat Get Status expire dengan status code 407', function (): void {
    $fixture = fixtureWebhookQris();

    Http::fake([
        '*/v2/QR-WEBHOOK-0001/status' => Http::response([
            'order_id' => 'QR-WEBHOOK-0001',
            'status_code' => '407',
            'status_message' => 'Success, transaction is found',
            'gross_amount' => '750000',
            'transaction_status' => 'expire',
        ]),
    ]);

    $this->postJson(route('webhooks.qris'), payloadWebhookQris($fixture['qris']->qr_ref, 'expire'))
        ->assertOk();

    expect($fixture['payment']->fresh()->status)->toBe(PaymentStatus::Kadaluarsa)
        ->and($fixture['rental']->fresh()->status)->toBe(RentalStatus::Dibatalkan)
        ->and($fixture['rental']->fresh()->cancelled_at)->not->toBeNull()
        ->and($fixture['qris']->fresh()->raw_payload['transaction_status'])->toBe('expire')
        ->and(BookingHistory::where('rental_id', $fixture['rental']->rental_id)->count())->toBe(2);
});
