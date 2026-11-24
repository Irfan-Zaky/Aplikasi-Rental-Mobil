<?php

namespace Tests\Feature;

use App\Enums\HandoverMethod;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\PaymentType;
use App\Enums\RentalStatus;
use App\Models\Account;
use App\Models\Car;
use App\Models\Payment;
use App\Models\Rental;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PemesananCarTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
    }

    public function test_alur_lengkap_f004_menyimpan_data_diri_di_rentals(): void
    {
        $customer = Account::factory()->create([
            'name' => 'Aku Customer',
            'no_hp' => '081234567890',
        ]);
        $mobil = Car::factory()->create([
            'status' => 'Tersedia',
            'harga_sewa' => 500000,
        ]);
        $mulai = now()->addDay()->toDateString();
        $selesai = now()->addDays(2)->toDateString();

        $this->actingAs($customer);

        $this->get(route('customer.detail-mobil', $mobil))
            ->assertOk()
            ->assertSee('Pilih Car');

        $this->get(route('customer.pilih-tanggal', ['id' => $mobil->car_id]))->assertOk();

        $this->get(route('customer.ketersediaan-mobil', [
            'id' => $mobil->car_id,
            'tanggal-mulai' => $mulai,
            'tanggal-selesai' => $selesai,
        ]))->assertOk()->assertSee('Car Tersedia');

        $this->get(route('customer.syarat'))->assertOk();
        $this->post(route('customer.syarat.store'), [])->assertSessionHasErrors('setuju_syarat');
        $this->post(route('customer.syarat.store'), ['setuju_syarat' => '1'])
            ->assertRedirect(route('customer.pilihan-layanan'));

        $this->post(route('customer.pilihan-layanan.store'), ['layanan' => 'lepas-kunci'])
            ->assertRedirect(route('customer.detail-layanan.lepas-kunci'));

        $this->get(route('customer.detail-layanan.lepas-kunci'))->assertOk();

        $this->post(route('customer.detail-layanan.store'), [
            'layanan' => 'lepas-kunci',
            'metode_penerimaan' => 'diantar',
            'lokasi_pengantaran' => 'Jl. Merdeka No. 1',
        ])->assertRedirect(route('customer.data-diri'));

        $this->get(route('customer.data-diri'))
            ->assertOk()
            ->assertSee('Aku Customer')
            ->assertSee('081234567890');

        $this->post(route('customer.data-diri.store'), [
            'nama_lengkap' => 'Aku Customer',
            'nomor_hp' => '081234567890',
            'nik' => '1234567890123456',
            'foto_ktp' => UploadedFile::fake()->image('ktp.jpg'),
            'foto_sim' => UploadedFile::fake()->image('sim.jpg'),
            'swafoto' => UploadedFile::fake()->image('selfie.jpg'),
        ])->assertRedirect(route('customer.ringkasan-pemesanan'));

        $this->get(route('customer.ringkasan-pemesanan'))
            ->assertOk()
            ->assertSee('Aku Customer')
            ->assertSee('Rp 1.000.000');

        $this->post(route('customer.ringkasan-pemesanan.store'))
            ->assertRedirect(route('customer.pilihan-pembayaran'));

        $rental = Rental::query()->firstOrFail();

        $this->assertSame(RentalStatus::MenungguPembayaran, $rental->status);
        $this->assertStringStartsWith('TRX-', $rental->trx_code);
        $this->assertSame(2, $rental->duration);
        $this->assertSame(500000, $rental->price_per_day);
        $this->assertSame(1000000, $rental->subtotal);
        $this->assertSame(1000000, $rental->total_rental);
        $this->assertSame('Aku Customer', $rental->customer_name);
        $this->assertSame('1234567890123456', $rental->customer_nik);
        $this->assertSame('081234567890', $rental->customer_phone);
        $this->assertNotNull($rental->terms_accepted_at);
        $this->assertfalse(Schema::hasColumn('account', 'nik'));
        $this->assertDatabaseMissing('rental', [
            'rental_id' => $rental->rental_id,
            'customer_nik' => '1234567890123456',
        ]);
        $this->assertDatabaseHas('add_on', [
            'rental_id' => $rental->rental_id,
            'handover_method' => HandoverMethod::Diantar->value,
            'pickup_point' => 'Jl. Merdeka No. 1',
            'driver_fee' => 0,
        ]);
        $this->assertDatabaseHas('booking_history', [
            'rental_id' => $rental->rental_id,
            'sequence_no' => 1,
            'status' => RentalStatus::MenungguPembayaran->value,
        ]);
        $this->assertGreaterThan(now(), $rental->expires_at);
        $this->assertLessThanOrEqual(now()->addMinutes(30), $rental->expires_at);
        $this->assertStringStartsWith('verifikasi/sim/', $rental->customer_sim_photo);
        Storage::disk('local')->assertExists($rental->customer_ktp_photo);
        Storage::disk('local')->assertExists($rental->customer_sim_photo);
        Storage::disk('local')->assertExists($rental->customer_selfie_photo);

        $this->get(route('customer.pilihan-pembayaran'))->assertOk()->assertSee('Rp 1.000.000');
    }

    public function test_data_diri_tidak_valid_tidak_melanjutkan_pemesanan(): void
    {
        $customer = Account::factory()->create();
        $mobil = Car::factory()->create(['status' => 'Tersedia']);

        $this->actingAs($customer);
        $this->simpanDraftHinggaDataDiri($mobil);

        $this->post(route('customer.data-diri.store'), [
            'nama_lengkap' => 'Nama Salah',
            'nomor_hp' => '081234567890',
            'nik' => '12345',
            'foto_ktp' => UploadedFile::fake()->image('ktp.jpg'),
        ])->assertSessionHasErrors(['nik', 'foto_sim', 'swafoto']);

        $this->assertDatabaseCount('rental', 0);
    }

    public function test_tanggal_terblokir_tidak_dapat_dipesan(): void
    {
        $customer = Account::factory()->create();
        $mobil = Car::factory()->create(['status' => 'Tersedia']);
        $mulai = now()->addDay()->toDateString();
        $selesai = now()->addDays(2)->toDateString();

        Rental::factory()->create([
            'car_id' => $mobil->car_id,
            'status' => RentalStatus::MenungguVerifikasi,
            'start_date' => now()->addDay(),
            'end_date' => now()->addDays(2),
        ]);

        $query = [
            'id' => $mobil->car_id,
            'tanggal-mulai' => $mulai,
            'tanggal-selesai' => $selesai,
        ];

        $this->actingAs($customer)->get(route('customer.ketersediaan-mobil', $query))
            ->assertRedirect(route('customer.pilih-tanggal', $query))
            ->assertSessionHas('modal_tidak_tersedia');
    }

    public function test_pembayaran_yang_sudah_kedaluwarsa_tidak_memblokir_tanggal(): void
    {
        $customer = Account::factory()->create();
        $mobil = Car::factory()->create(['status' => 'Tersedia']);
        $mulai = now()->addDay()->toDateString();
        $selesai = now()->addDays(2)->toDateString();

        Rental::factory()->create([
            'car_id' => $mobil->car_id,
            'status' => RentalStatus::MenungguPembayaran,
            'start_date' => now()->addDay(),
            'end_date' => now()->addDays(2),
            'expires_at' => now()->subMinute(),
        ]);

        $this->actingAs($customer)->get(route('customer.ketersediaan-mobil', [
            'id' => $mobil->car_id,
            'tanggal-mulai' => $mulai,
            'tanggal-selesai' => $selesai,
        ]))->assertOk()->assertSee('Car Tersedia');
    }

    public function test_halaman_qris_membuat_payment_sewa_dan_detail_qris(): void
    {
        $this->travelTo('2026-11-24 10:00:00');

        $customer = Account::factory()->create();
        $rental = Rental::factory()->create([
            'account_id' => $customer->account_id,
            'status' => RentalStatus::MenungguPembayaran,
            'total_rental' => 750000,
        ]);

        $this->actingAs($customer)
            ->withSession(['pemesanan_terakhir' => $rental->rental_id])
            ->get(route('customer.pembayaran-qris'))
            ->assertOk()
            ->assertSee('Rp 750.000');

        $payment = $rental->payments()->firstOrFail();

        expect($payment->method)->toBe(PaymentMethod::QRIS)
            ->and($payment->type)->toBe(PaymentType::Sewa)
            ->and($payment->status)->toBe(PaymentStatus::MenungguPembayaran)
            ->and($payment->amount)->toBe(750000)
            ->and($payment->expires_at->toDateTimeString())->toBe('2026-11-24 10:10:00')
            ->and($payment->qris)->not->toBeNull();

        expect($rental->fresh()->expires_at->toDateTimeString())->toBe('2026-11-24 10:10:00');

        $this->assertDatabaseCount('payment', 1);
    }

    public function test_halaman_tunai_membuat_kode_dan_memakai_ulang_payment(): void
    {
        $this->travelTo('2026-11-24 10:00:00');

        $customer = Account::factory()->create();
        $rental = Rental::factory()->create([
            'account_id' => $customer->account_id,
            'status' => RentalStatus::MenungguPembayaran,
            'total_rental' => 500000,
        ]);

        $this->actingAs($customer)
            ->withSession(['pemesanan_terakhir' => $rental->rental_id])
            ->get(route('customer.pembayaran-tunai'))
            ->assertOk()
            ->assertSee('Rp 500.000');

        $payment = $rental->payments()->firstOrFail();

        expect($payment->method)->toBe(PaymentMethod::Tunai)
            ->and($payment->expires_at->toDateTimeString())->toBe('2026-11-24 13:00:00')
            ->and($payment->cash->payment_code)->toMatch('/^TNI-\d{4}-\d{4}$/');

        expect($rental->fresh()->expires_at->toDateTimeString())->toBe('2026-11-24 13:00:00');

        $this->get(route('customer.pembayaran-tunai'))->assertOk();
        $this->assertDatabaseCount('payment', 1);
    }

    public function test_halaman_pembayaran_menolak_pesanan_milik_customer_lain(): void
    {
        $customer = Account::factory()->create();
        $rental = Rental::factory()->create([
            'status' => RentalStatus::MenungguPembayaran,
        ]);

        $this->actingAs($customer)
            ->withSession(['pemesanan_terakhir' => $rental->rental_id])
            ->get(route('customer.pembayaran-qris'))
            ->assertRedirect(route('customer.katalog'));

        $this->assertDatabaseCount('payment', 0);
    }

    public function test_halaman_pembayaran_mengarahkan_ke_metode_payment_aktif(): void
    {
        $customer = Account::factory()->create();
        $rental = Rental::factory()->create([
            'account_id' => $customer->account_id,
            'status' => RentalStatus::MenungguPembayaran,
        ]);
        $payment = Payment::factory()->create([
            'rental_id' => $rental->rental_id,
            'method' => PaymentMethod::Tunai,
            'status' => PaymentStatus::MenungguPembayaran,
        ]);
        $payment->cash()->create(['payment_code' => 'TNI-1234-5678']);

        $this->actingAs($customer)
            ->withSession(['pemesanan_terakhir' => $rental->rental_id])
            ->get(route('customer.pembayaran-qris'))
            ->assertRedirect(route('customer.pembayaran-tunai'));

        $this->assertDatabaseCount('payment', 1);
    }

    public function test_kode_transaksi_mengikuti_rental_id_terbesar(): void
    {
        $customer = Account::factory()->create();
        $mobil = Car::factory()->create([
            'status' => 'Tersedia',
            'harga_sewa' => 500000,
        ]);

        $rentalLama = Rental::factory()->create([
            'car_id' => $mobil->car_id,
            'status' => RentalStatus::Selesai,
            'start_date' => now()->subDays(10),
            'end_date' => now()->subDays(8),
        ]);

        $this->actingAs($customer);
        $this->simpanDraftHinggaDataDiri($mobil);

        $this->post(route('customer.data-diri.store'), [
            'nama_lengkap' => 'Aku Customer',
            'nomor_hp' => '081234567890',
            'nik' => '1234567890123456',
            'foto_ktp' => UploadedFile::fake()->image('ktp.jpg'),
            'foto_sim' => UploadedFile::fake()->image('sim.jpg'),
            'swafoto' => UploadedFile::fake()->image('selfie.jpg'),
        ])->assertRedirect(route('customer.ringkasan-pemesanan'));

        $this->post(route('customer.ringkasan-pemesanan.store'))
            ->assertRedirect(route('customer.pilihan-pembayaran'));

        $rentalBaru = Rental::query()->whereKeyNot($rentalLama->getKey())->firstOrFail();

        $this->assertSame(
            'TRX-'.str_pad((string) $rentalBaru->rental_id, 6, '0', STR_PAD_LEFT),
            $rentalBaru->trx_code,
        );
    }

    public function test_dengan_sopir_menambahkan_biaya_sopir_ke_total(): void
    {
        config()->set('rental.driver_fee_per_day', 50000);

        $customer = Account::factory()->create();
        $mobil = Car::factory()->create([
            'status' => 'Tersedia',
            'harga_sewa' => 500000,
        ]);

        $this->actingAs($customer);
        $this->simpanDraftHinggaDataDiri($mobil, 'dengan-sopir');

        $this->post(route('customer.data-diri.store'), [
            'nama_lengkap' => 'Aku Customer',
            'nomor_hp' => '081234567890',
            'nik' => '1234567890123456',
            'foto_ktp' => UploadedFile::fake()->image('ktp.jpg'),
            'foto_sim' => UploadedFile::fake()->image('sim.jpg'),
            'swafoto' => UploadedFile::fake()->image('selfie.jpg'),
        ])->assertRedirect(route('customer.ringkasan-pemesanan'));

        $this->post(route('customer.ringkasan-pemesanan.store'))
            ->assertRedirect(route('customer.pilihan-pembayaran'));

        $rental = Rental::query()->firstOrFail();

        $this->assertSame(1000000, $rental->subtotal);
        $this->assertSame(1100000, $rental->total_rental);
        $this->assertDatabaseHas('add_on', [
            'rental_id' => $rental->rental_id,
            'handover_method' => null,
            'pickup_point' => 'Jl. Sudirman No. 2',
            'driver_fee' => 100000,
        ]);
    }

    public function test_tamu_tidak_dapat_membuka_halaman_pemesanan(): void
    {
        $mobil = Car::factory()->create();

        $this->get(route('customer.detail-mobil', $mobil))
            ->assertRedirect(route('customer.login'));
        $this->get(route('customer.pilih-tanggal', ['id' => $mobil->car_id]))
            ->assertRedirect(route('customer.login'));
        $this->get(route('customer.data-diri'))->assertRedirect(route('customer.login'));
    }

    /**
     * Menyiapkan draft dari C-08 sampai C-11 untuk pengujian C-12/C-13.
     */
    private function simpanDraftHinggaDataDiri(Car $mobil, string $layanan = 'lepas-kunci'): void
    {
        $this->get(route('customer.ketersediaan-mobil', [
            'id' => $mobil->car_id,
            'tanggal-mulai' => now()->addDay()->toDateString(),
            'tanggal-selesai' => now()->addDays(2)->toDateString(),
        ]))->assertOk();

        $this->post(route('customer.syarat.store'), ['setuju_syarat' => '1'])
            ->assertRedirect(route('customer.pilihan-layanan'));

        $this->post(route('customer.pilihan-layanan.store'), ['layanan' => $layanan]);

        if ($layanan === 'lepas-kunci') {
            $this->post(route('customer.detail-layanan.store'), [
                'layanan' => 'lepas-kunci',
                'metode_penerimaan' => 'diantar',
                'lokasi_pengantaran' => 'Jl. Merdeka No. 1',
            ])->assertRedirect(route('customer.data-diri'));

            return;
        }

        $this->post(route('customer.detail-layanan.store'), [
            'layanan' => 'dengan-sopir',
            'alamat_penjemputan' => 'Jl. Sudirman No. 2',
        ])->assertRedirect(route('customer.data-diri'));
    }
}
