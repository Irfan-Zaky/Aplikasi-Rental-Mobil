<?php

namespace Tests\Feature;

use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\RentalStatus;
use App\Models\Mobil;
use App\Models\Payment;
use App\Models\Rental;
use App\Models\RentalAddOn;
use App\Models\User;
use Database\Seeders\TransaksiSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class TransaksiFilterDanRiwayatTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $customer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create(['role' => 'admin']);
        $this->customer = User::factory()->create(['role' => 'user']);
    }

    public function test_transaksi_seeder_berhasil_dijalankan(): void
    {
        Storage::fake('local');

        $this->seed(TransaksiSeeder::class);

        $this->assertDatabaseHas('users', ['email' => 'octaimub@gmail.com', 'role' => 'admin']);
        $this->assertDatabaseHas('users', ['email' => 'fadhilkece@gmail.com', 'role' => 'user']);
        $this->assertDatabaseHas('rentals', ['status' => RentalStatus::MenungguPembayaran->value]);
        $this->assertDatabaseHas('rentals', ['status' => RentalStatus::Berjalan->value]);
        $this->assertDatabaseHas('rentals', ['status' => RentalStatus::Selesai->value]);
        $this->assertDatabaseHas('rentals', ['status' => RentalStatus::Dibatalkan->value]);
        Storage::disk('local')->assertExists('verifikasi/ktp/ktp_demo.jpg');
        Storage::disk('local')->assertExists('verifikasi/selfie/selfie_demo.jpg');
    }

    public function test_admin_transaksi_hanya_menampilkan_transaksi_belum_selesai(): void
    {
        $mobil = Mobil::factory()->create(['merk' => 'Toyota', 'nama_mobil' => 'Avanza']);

        // Transaksi belum selesai
        $rentalAktif = Rental::factory()->create([
            'mobil_id' => $mobil->id,
            'user_id' => $this->customer->id,
            'trx_code' => 'TRX-AKTIF-01',
            'customer_name' => 'Budi Aktif',
            'status' => RentalStatus::Berjalan,
        ]);

        // Transaksi sudah selesai & dibatalkan
        $rentalSelesai = Rental::factory()->create([
            'mobil_id' => $mobil->id,
            'user_id' => $this->customer->id,
            'trx_code' => 'TRX-SELESAI-01',
            'customer_name' => 'Joko Selesai',
            'status' => RentalStatus::Selesai,
        ]);

        $response = $this->actingAs($this->admin)->get(route('admin.transaksi'));

        $response->assertOk();
        $response->assertSee('TRX-AKTIF-01');
        $response->assertSee('Budi Aktif');
        $response->assertDontSee('TRX-SELESAI-01');
        $response->assertDontSee('Joko Selesai');
    }

    public function test_admin_transaksi_filter_qris_dan_tunai(): void
    {
        $mobil = Mobil::factory()->create(['merk' => 'Honda', 'nama_mobil' => 'Brio']);

        // Rental 1: QRIS
        $rentalQris = Rental::factory()->create([
            'mobil_id' => $mobil->id,
            'user_id' => $this->customer->id,
            'trx_code' => 'TRX-QRIS-99',
            'customer_name' => 'Pelanggan QRIS',
            'status' => RentalStatus::MenungguVerifikasi,
        ]);
        Payment::factory()->create([
            'rental_id' => $rentalQris->id,
            'method' => PaymentMethod::QRIS,
            'status' => PaymentStatus::MenungguKonfirmasi,
        ]);

        // Rental 2: Tunai
        $rentalTunai = Rental::factory()->create([
            'mobil_id' => $mobil->id,
            'user_id' => $this->customer->id,
            'trx_code' => 'TRX-TUNAI-88',
            'customer_name' => 'Pelanggan Tunai',
            'status' => RentalStatus::MenungguPembayaran,
        ]);
        Payment::factory()->create([
            'rental_id' => $rentalTunai->id,
            'method' => PaymentMethod::Tunai,
            'status' => PaymentStatus::MenungguPembayaran,
        ]);

        // Filter QRIS
        $respQris = $this->actingAs($this->admin)->get(route('admin.transaksi', ['filter' => 'qris']));
        $respQris->assertOk();
        $respQris->assertSee('TRX-QRIS-99');
        $respQris->assertDontSee('TRX-TUNAI-88');

        // Filter Tunai
        $respTunai = $this->actingAs($this->admin)->get(route('admin.transaksi', ['filter' => 'tunai']));
        $respTunai->assertOk();
        $respTunai->assertSee('TRX-TUNAI-88');
        $respTunai->assertDontSee('TRX-QRIS-99');
    }

    public function test_admin_transaksi_filter_penyelesaian(): void
    {
        $mobil = Mobil::factory()->create(['merk' => 'Daihatsu', 'nama_mobil' => 'Xenia']);

        // Unit di customer (Berjalan)
        $rentalBerjalan = Rental::factory()->create([
            'mobil_id' => $mobil->id,
            'user_id' => $this->customer->id,
            'trx_code' => 'TRX-PENYELESAIAN-01',
            'status' => RentalStatus::Berjalan,
        ]);

        // Unit baru booking (Menunggu Verifikasi)
        $rentalMenunggu = Rental::factory()->create([
            'mobil_id' => $mobil->id,
            'user_id' => $this->customer->id,
            'trx_code' => 'TRX-BELUM-JALAN-02',
            'status' => RentalStatus::MenungguVerifikasi,
        ]);

        $response = $this->actingAs($this->admin)->get(route('admin.transaksi', ['filter' => 'penyelesaian']));
        $response->assertOk();
        $response->assertSee('TRX-PENYELESAIAN-01');
        $response->assertDontSee('TRX-BELUM-JALAN-02');
    }

    public function test_admin_transaksi_pencarian_kata_kunci(): void
    {
        $mobilA = Mobil::factory()->create(['merk' => 'Toyota', 'nama_mobil' => 'Fortuner']);
        $mobilB = Mobil::factory()->create(['merk' => 'Suzuki', 'nama_mobil' => 'Ertiga']);

        Rental::factory()->create([
            'mobil_id' => $mobilA->id,
            'user_id' => $this->customer->id,
            'customer_name' => 'Ahmad Khusus',
            'trx_code' => 'TRX-KHUSUS-01',
            'status' => RentalStatus::MenungguPembayaran,
        ]);

        Rental::factory()->create([
            'mobil_id' => $mobilB->id,
            'user_id' => $this->customer->id,
            'customer_name' => 'Siti Umum',
            'trx_code' => 'TRX-UMUM-02',
            'status' => RentalStatus::MenungguPembayaran,
        ]);

        $response = $this->actingAs($this->admin)->get(route('admin.transaksi', ['keyword' => 'Ahmad Khusus']));
        $response->assertOk();
        $response->assertSee('TRX-KHUSUS-01');
        $response->assertDontSee('TRX-UMUM-02');
    }

    public function test_admin_riwayat_transaksi_hanya_menampilkan_transaksi_selesai_dan_dibatalkan(): void
    {
        $mobil = Mobil::factory()->create(['merk' => 'Toyota', 'nama_mobil' => 'Avanza']);

        $rentalSelesai = Rental::factory()->create([
            'mobil_id' => $mobil->id,
            'user_id' => $this->customer->id,
            'trx_code' => 'TRX-ARSIP-SELESAI',
            'status' => RentalStatus::Selesai,
        ]);

        $rentalBatal = Rental::factory()->create([
            'mobil_id' => $mobil->id,
            'user_id' => $this->customer->id,
            'trx_code' => 'TRX-ARSIP-BATAL',
            'status' => RentalStatus::Dibatalkan,
        ]);

        $rentalBerjalan = Rental::factory()->create([
            'mobil_id' => $mobil->id,
            'user_id' => $this->customer->id,
            'trx_code' => 'TRX-BELUM-SELESAI',
            'status' => RentalStatus::Berjalan,
        ]);

        $response = $this->actingAs($this->admin)->get(route('admin.riwayat-transaksi'));
        $response->assertOk();
        $response->assertSee('TRX-ARSIP-SELESAI');
        $response->assertSee('TRX-ARSIP-BATAL');
        $response->assertDontSee('TRX-BELUM-SELESAI');
    }

    public function test_admin_riwayat_transaksi_filter_merk_dinamis_dari_database(): void
    {
        $mobilToyota = Mobil::factory()->create(['merk' => 'Toyota', 'nama_mobil' => 'Innova']);
        $mobilHonda = Mobil::factory()->create(['merk' => 'Honda', 'nama_mobil' => 'HRV']);

        Rental::factory()->create([
            'mobil_id' => $mobilToyota->id,
            'user_id' => $this->customer->id,
            'trx_code' => 'TRX-TOYOTA-SELESAI',
            'status' => RentalStatus::Selesai,
        ]);

        Rental::factory()->create([
            'mobil_id' => $mobilHonda->id,
            'user_id' => $this->customer->id,
            'trx_code' => 'TRX-HONDA-SELESAI',
            'status' => RentalStatus::Selesai,
        ]);

        $response = $this->actingAs($this->admin)->get(route('admin.riwayat-transaksi'));
        $response->assertOk();

        // Verifikasi opsi select berisi merk yang ada di database
        $response->assertSee('<option value="Toyota"', false);
        $response->assertSee('<option value="Honda"', false);

        // Filter berdasarkan merk Toyota
        $filterResp = $this->actingAs($this->admin)->get(route('admin.riwayat-transaksi', ['merk' => 'Toyota']));
        $filterResp->assertOk();
        $filterResp->assertSee('TRX-TOYOTA-SELESAI');
        $filterResp->assertDontSee('TRX-HONDA-SELESAI');
    }

    public function test_customer_riwayat_menampilkan_seluruh_transaksi_milik_customer_dan_filter_tab(): void
    {
        $mobil = Mobil::factory()->create(['merk' => 'Toyota', 'nama_mobil' => 'Avanza']);

        // Transaksi customer yang sedang login: 1 aktif, 1 selesai
        $rentalAktif = Rental::factory()->create([
            'user_id' => $this->customer->id,
            'mobil_id' => $mobil->id,
            'trx_code' => 'TRX-CUST-AKTIF',
            'status' => RentalStatus::Berjalan,
        ]);

        $rentalSelesai = Rental::factory()->create([
            'user_id' => $this->customer->id,
            'mobil_id' => $mobil->id,
            'trx_code' => 'TRX-CUST-SELESAI',
            'status' => RentalStatus::Selesai,
        ]);

        // Transaksi milik customer lain (tidak boleh terlihat)
        $customerLain = User::factory()->create(['role' => 'user']);
        $rentalLain = Rental::factory()->create([
            'user_id' => $customerLain->id,
            'mobil_id' => $mobil->id,
            'trx_code' => 'TRX-ORANG-LAIN',
            'status' => RentalStatus::Berjalan,
        ]);

        // 1. Tab Semua
        $respSemua = $this->actingAs($this->customer)->get(route('customer.riwayat', ['tab' => 'semua']));
        $respSemua->assertOk();
        $respSemua->assertSee('TRX-CUST-AKTIF');
        $respSemua->assertSee('TRX-CUST-SELESAI');
        $respSemua->assertDontSee('TRX-ORANG-LAIN');

        // 2. Tab Aktif
        $respAktif = $this->actingAs($this->customer)->get(route('customer.riwayat', ['tab' => 'aktif']));
        $respAktif->assertOk();
        $respAktif->assertSee('TRX-CUST-AKTIF');
        $respAktif->assertDontSee('TRX-CUST-SELESAI');

        // 3. Tab Selesai
        $respSelesai = $this->actingAs($this->customer)->get(route('customer.riwayat', ['tab' => 'selesai']));
        $respSelesai->assertOk();
        $respSelesai->assertSee('TRX-CUST-SELESAI');
        $respSelesai->assertDontSee('TRX-CUST-AKTIF');
    }

    public function test_customer_detail_pesanan_dapat_diakses_oleh_pemilik(): void
    {
        $mobil = Mobil::factory()->create(['merk' => 'Toyota', 'nama_mobil' => 'Avanza']);

        $rental = Rental::factory()->create([
            'user_id' => $this->customer->id,
            'mobil_id' => $mobil->id,
            'booking_code' => 'BK-TEST99',
            'status' => RentalStatus::Berjalan,
        ]);
        RentalAddOn::create([
            'rental_id' => $rental->id,
            'handover_method' => 'Ambil di garasi',
            'driver_fee' => 0,
        ]);

        $response = $this->actingAs($this->customer)->get(route('customer.detail-pesanan', ['id' => $rental->id]));
        $response->assertOk();
        $response->assertSee('BK-TEST99');
        $response->assertSee('Detail Pesanan');
    }
}
