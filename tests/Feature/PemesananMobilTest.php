<?php

namespace Tests\Feature;

use App\Enums\HandoverMethod;
use App\Enums\RentalStatus;
use App\Models\Mobil;
use App\Models\Rental;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PemesananMobilTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
    }

    public function test_alur_lengkap_f004_menyimpan_data_diri_di_rentals(): void
    {
        $customer = User::factory()->create([
            'name' => 'Aku Customer',
            'no_hp' => '081234567890',
        ]);
        $mobil = Mobil::factory()->create([
            'status' => 'Tersedia',
            'harga_sewa' => 500000,
        ]);
        $mulai = now()->addDay()->toDateString();
        $selesai = now()->addDays(2)->toDateString();

        $this->actingAs($customer);

        $this->get(route('customer.detail-mobil', $mobil))
            ->assertOk()
            ->assertSee('Pilih Mobil');

        $this->get(route('customer.pilih-tanggal', ['id' => $mobil->id]))->assertOk();

        $this->get(route('customer.ketersediaan-mobil', [
            'id' => $mobil->id,
            'tanggal-mulai' => $mulai,
            'tanggal-selesai' => $selesai,
        ]))->assertOk()->assertSee('Mobil Tersedia');

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
        $this->assertfalse(Schema::hasColumn('users', 'nik'));
        $this->assertDatabaseMissing('rentals', [
            'id' => $rental->id,
            'customer_nik' => '1234567890123456',
        ]);
        $this->assertDatabaseHas('rental_add_ons', [
            'rental_id' => $rental->id,
            'handover_method' => HandoverMethod::Diantar->value,
            'pickup_point' => 'Jl. Merdeka No. 1',
            'driver_fee' => 0,
        ]);
        $this->assertDatabaseHas('booking_histories', [
            'rental_id' => $rental->id,
            'sequence_no' => 1,
            'status' => RentalStatus::MenungguPembayaran->value,
        ]);
        $this->assertGreaterThan(now(), $rental->expires_at);
        $this->assertLessThanOrEqual(now()->addMinutes(30), $rental->expires_at);
        Storage::disk('local')->assertExists($rental->customer_ktp_photo);
        Storage::disk('local')->assertExists($rental->customer_selfie_photo);

        $this->get(route('customer.pilihan-pembayaran'))->assertOk()->assertSee('Rp 1.000.000');
    }

    public function test_data_diri_tidak_valid_tidak_melanjutkan_pemesanan(): void
    {
        $customer = User::factory()->create();
        $mobil = Mobil::factory()->create(['status' => 'Tersedia']);

        $this->actingAs($customer);
        $this->simpanDraftHinggaDataDiri($mobil);

        $this->post(route('customer.data-diri.store'), [
            'nama_lengkap' => 'Nama Salah',
            'nomor_hp' => '081234567890',
            'nik' => '12345',
            'foto_ktp' => UploadedFile::fake()->image('ktp.jpg'),
        ])->assertSessionHasErrors(['nik', 'swafoto']);

        $this->assertDatabaseCount('rentals', 0);
    }

    public function test_tanggal_terblokir_tidak_dapat_dipesan(): void
    {
        $customer = User::factory()->create();
        $mobil = Mobil::factory()->create(['status' => 'Tersedia']);
        $mulai = now()->addDay()->toDateString();
        $selesai = now()->addDays(2)->toDateString();

        Rental::factory()->create([
            'mobil_id' => $mobil->id,
            'status' => RentalStatus::MenungguVerifikasi,
            'start_date' => now()->addDay(),
            'end_date' => now()->addDays(2),
        ]);

        $this->actingAs($customer)->get(route('customer.ketersediaan-mobil', [
            'id' => $mobil->id,
            'tanggal-mulai' => $mulai,
            'tanggal-selesai' => $selesai,
        ]))->assertSessionHasErrors('tanggal-mulai');
    }

    public function test_pembayaran_yang_sudah_kedaluwarsa_tidak_memblokir_tanggal(): void
    {
        $customer = User::factory()->create();
        $mobil = Mobil::factory()->create(['status' => 'Tersedia']);
        $mulai = now()->addDay()->toDateString();
        $selesai = now()->addDays(2)->toDateString();

        Rental::factory()->create([
            'mobil_id' => $mobil->id,
            'status' => RentalStatus::MenungguPembayaran,
            'start_date' => now()->addDay(),
            'end_date' => now()->addDays(2),
            'expires_at' => now()->subMinute(),
        ]);

        $this->actingAs($customer)->get(route('customer.ketersediaan-mobil', [
            'id' => $mobil->id,
            'tanggal-mulai' => $mulai,
            'tanggal-selesai' => $selesai,
        ]))->assertOk()->assertSee('Mobil Tersedia');
    }

    public function test_dengan_sopir_menambahkan_biaya_sopir_ke_total(): void
    {
        config()->set('rental.driver_fee_per_day', 50000);

        $customer = User::factory()->create();
        $mobil = Mobil::factory()->create([
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
            'swafoto' => UploadedFile::fake()->image('selfie.jpg'),
        ])->assertRedirect(route('customer.ringkasan-pemesanan'));

        $this->post(route('customer.ringkasan-pemesanan.store'))
            ->assertRedirect(route('customer.pilihan-pembayaran'));

        $rental = Rental::query()->firstOrFail();

        $this->assertSame(1000000, $rental->subtotal);
        $this->assertSame(1100000, $rental->total_rental);
        $this->assertDatabaseHas('rental_add_ons', [
            'rental_id' => $rental->id,
            'handover_method' => null,
            'pickup_point' => 'Jl. Sudirman No. 2',
            'driver_fee' => 100000,
        ]);
    }

    public function test_tamu_tidak_dapat_membuka_halaman_pemesanan(): void
    {
        $mobil = Mobil::factory()->create();

        $this->get(route('customer.detail-mobil', $mobil))
            ->assertRedirect(route('customer.login'));
        $this->get(route('customer.pilih-tanggal', ['id' => $mobil->id]))
            ->assertRedirect(route('customer.login'));
        $this->get(route('customer.data-diri'))->assertRedirect(route('customer.login'));
    }

    /**
     * Menyiapkan draft dari C-08 sampai C-11 untuk pengujian C-12/C-13.
     */
    private function simpanDraftHinggaDataDiri(Mobil $mobil, string $layanan = 'lepas-kunci'): void
    {
        $this->get(route('customer.ketersediaan-mobil', [
            'id' => $mobil->id,
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
