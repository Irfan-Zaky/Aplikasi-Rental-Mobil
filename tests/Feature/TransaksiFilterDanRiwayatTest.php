<?php

namespace Tests\Feature;

use App\Enums\CarStatus;
use App\Enums\HandoverMethod;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\PaymentType;
use App\Enums\RentalStatus;
use App\Models\Account;
use App\Models\AddOn;
use App\Models\BookingHistory;
use App\Models\Car;
use App\Models\Payment;
use App\Models\Qris;
use App\Models\Rental;
use Database\Seeders\TransaksiSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class TransaksiFilterDanRiwayatTest extends TestCase
{
    use RefreshDatabase;

    private Account $admin;

    private Account $customer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = Account::factory()->create(['role' => 'admin']);
        $this->customer = Account::factory()->create(['role' => 'user']);
    }

    public function test_transaksi_seeder_berhasil_dijalankan(): void
    {
        Storage::fake('local');

        $this->seed(TransaksiSeeder::class);

        $this->assertDatabaseHas('account', ['email' => 'octaimub@gmail.com', 'role' => 'admin']);
        $this->assertDatabaseHas('account', ['email' => 'fadhilkece@gmail.com', 'role' => 'user']);
        $this->assertDatabaseHas('rental', ['status' => RentalStatus::MenungguPembayaran->value]);
        $this->assertDatabaseHas('rental', ['status' => RentalStatus::Berjalan->value]);
        $this->assertDatabaseHas('rental', ['status' => RentalStatus::Selesai->value]);
        $this->assertDatabaseHas('rental', ['status' => RentalStatus::Dibatalkan->value]);
        Storage::disk('local')->assertExists('verifikasi/ktp/ktp_demo.jpg');
        Storage::disk('local')->assertExists('verifikasi/selfie/selfie_demo.jpg');
    }

    public function test_admin_transaksi_hanya_menampilkan_transaksi_belum_selesai(): void
    {
        $mobil = Car::factory()->create(['merk' => 'Toyota', 'nama_mobil' => 'Avanza']);

        // Transaksi belum selesai
        $rentalAktif = Rental::factory()->create([
            'car_id' => $mobil->car_id,
            'account_id' => $this->customer->account_id,
            'trx_code' => 'TRX-AKTIF-01',
            'customer_name' => 'Budi Aktif',
            'status' => RentalStatus::Berjalan,
        ]);

        // Transaksi sudah selesai & dibatalkan
        $rentalSelesai = Rental::factory()->create([
            'car_id' => $mobil->car_id,
            'account_id' => $this->customer->account_id,
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
        $mobil = Car::factory()->create(['merk' => 'Honda', 'nama_mobil' => 'Brio']);

        // Rental 1: QRIS
        $rentalQris = Rental::factory()->create([
            'car_id' => $mobil->car_id,
            'account_id' => $this->customer->account_id,
            'trx_code' => 'TRX-QRIS-99',
            'customer_name' => 'Pelanggan QRIS',
            'status' => RentalStatus::MenungguVerifikasi,
        ]);
        Payment::factory()->create([
            'rental_id' => $rentalQris->rental_id,
            'method' => PaymentMethod::QRIS,
            'status' => PaymentStatus::MenungguKonfirmasi,
        ]);

        // Rental 2: Tunai
        $rentalTunai = Rental::factory()->create([
            'car_id' => $mobil->car_id,
            'account_id' => $this->customer->account_id,
            'trx_code' => 'TRX-TUNAI-88',
            'customer_name' => 'Pelanggan Tunai',
            'status' => RentalStatus::MenungguPembayaran,
        ]);
        Payment::factory()->create([
            'rental_id' => $rentalTunai->rental_id,
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
        $mobil = Car::factory()->create(['merk' => 'Daihatsu', 'nama_mobil' => 'Xenia']);

        // Unit di customer (Berjalan)
        $rentalBerjalan = Rental::factory()->create([
            'car_id' => $mobil->car_id,
            'account_id' => $this->customer->account_id,
            'trx_code' => 'TRX-PENYELESAIAN-01',
            'status' => RentalStatus::Berjalan,
        ]);

        // Unit baru booking (Menunggu Verifikasi)
        $rentalMenunggu = Rental::factory()->create([
            'car_id' => $mobil->car_id,
            'account_id' => $this->customer->account_id,
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
        $mobilA = Car::factory()->create(['merk' => 'Toyota', 'nama_mobil' => 'Fortuner']);
        $mobilB = Car::factory()->create(['merk' => 'Suzuki', 'nama_mobil' => 'Ertiga']);

        Rental::factory()->create([
            'car_id' => $mobilA->car_id,
            'account_id' => $this->customer->account_id,
            'customer_name' => 'Ahmad Khusus',
            'trx_code' => 'TRX-KHUSUS-01',
            'status' => RentalStatus::MenungguPembayaran,
        ]);

        Rental::factory()->create([
            'car_id' => $mobilB->car_id,
            'account_id' => $this->customer->account_id,
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
        $mobil = Car::factory()->create(['merk' => 'Toyota', 'nama_mobil' => 'Avanza']);

        $rentalSelesai = Rental::factory()->create([
            'car_id' => $mobil->car_id,
            'account_id' => $this->customer->account_id,
            'trx_code' => 'TRX-ARSIP-SELESAI',
            'status' => RentalStatus::Selesai,
        ]);

        $rentalBatal = Rental::factory()->create([
            'car_id' => $mobil->car_id,
            'account_id' => $this->customer->account_id,
            'trx_code' => 'TRX-ARSIP-BATAL',
            'status' => RentalStatus::Dibatalkan,
        ]);

        $rentalBerjalan = Rental::factory()->create([
            'car_id' => $mobil->car_id,
            'account_id' => $this->customer->account_id,
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
        $mobilToyota = Car::factory()->create(['merk' => 'Toyota', 'nama_mobil' => 'Innova']);
        $mobilHonda = Car::factory()->create(['merk' => 'Honda', 'nama_mobil' => 'HRV']);

        Rental::factory()->create([
            'car_id' => $mobilToyota->car_id,
            'account_id' => $this->customer->account_id,
            'trx_code' => 'TRX-TOYOTA-SELESAI',
            'status' => RentalStatus::Selesai,
        ]);

        Rental::factory()->create([
            'car_id' => $mobilHonda->car_id,
            'account_id' => $this->customer->account_id,
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
        $mobil = Car::factory()->create(['merk' => 'Toyota', 'nama_mobil' => 'Avanza']);

        // Transaksi customer yang sedang login: 1 aktif, 1 selesai
        $rentalAktif = Rental::factory()->create([
            'account_id' => $this->customer->account_id,
            'car_id' => $mobil->car_id,
            'trx_code' => 'TRX-CUST-AKTIF',
            'status' => RentalStatus::Berjalan,
        ]);

        $rentalSelesai = Rental::factory()->create([
            'account_id' => $this->customer->account_id,
            'car_id' => $mobil->car_id,
            'trx_code' => 'TRX-CUST-SELESAI',
            'status' => RentalStatus::Selesai,
        ]);

        // Transaksi milik customer lain (tidak boleh terlihat)
        $customerLain = Account::factory()->create(['role' => 'user']);
        $rentalLain = Rental::factory()->create([
            'account_id' => $customerLain->account_id,
            'car_id' => $mobil->car_id,
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

    public function test_detail_transaksi_menampilkan_lokasi_pengantaran_diantar(): void
    {
        $mobil = Car::factory()->create(['merk' => 'Toyota', 'nama_mobil' => 'Avanza']);
        $rental = Rental::factory()->create([
            'account_id' => $this->customer->account_id,
            'car_id' => $mobil->car_id,
            'status' => RentalStatus::Berjalan,
        ]);

        AddOn::create([
            'rental_id' => $rental->rental_id,
            'handover_method' => HandoverMethod::Diantar,
            'pickup_point' => 'Jl. Merdeka No. 45',
            'driver_fee' => 0,
        ]);

        $this->actingAs($this->admin)
            ->get(route('admin.transaksi.show', $rental))
            ->assertOk()
            ->assertSee('Diantar (Jl. Merdeka No. 45)');

        $this->actingAs($this->customer)
            ->get(route('customer.detail-pesanan', ['id' => $rental->rental_id]))
            ->assertOk()
            ->assertSee('Diantar (Jl. Merdeka No. 45)');
    }

    public function test_admin_mengonfirmasi_pembayaran_dan_menerbitkan_kode_booking(): void
    {
        $this->travelTo('2026-11-24 10:00:00');

        $rental = Rental::factory()->create([
            'account_id' => $this->customer->account_id,
            'status' => RentalStatus::MenungguVerifikasi,
        ]);
        $payment = Payment::factory()->create([
            'rental_id' => $rental->rental_id,
            'type' => PaymentType::Sewa,
            'method' => PaymentMethod::QRIS,
            'status' => PaymentStatus::MenungguKonfirmasi,
            'amount' => 750000,
        ]);

        $this->actingAs($this->admin)
            ->post(route('admin.transaksi.konfirmasi-pembayaran', $rental))
            ->assertRedirect();

        $this->assertDatabaseHas('payment', [
            'payment_id' => $payment->payment_id,
            'status' => PaymentStatus::Berhasil->value,
            'confirmed_by' => $this->admin->account_id,
            'paid_at' => '2026-11-24 10:00:00',
            'confirmed_at' => '2026-11-24 10:00:00',
        ]);

        $rental->refresh();

        expect($rental->status)->toBe(RentalStatus::PembayaranBerhasil)
            ->and($rental->booking_code)->toStartWith('BK-')->toHaveLength(9)
            ->and($rental->invoice_number)->toBe('INV-2026-0001')
            ->and($rental->invoice_issued_at->toDateTimeString())->toBe('2026-11-24 10:00:00');

        $this->assertDatabaseHas('rental', [
            'rental_id' => $rental->rental_id,
            'status' => RentalStatus::PembayaranBerhasil->value,
            'booking_code' => $rental->booking_code,
            'invoice_number' => $rental->invoice_number,
        ]);
        $this->assertDatabaseHas('booking_history', [
            'rental_id' => $rental->rental_id,
            'sequence_no' => 1,
            'status' => RentalStatus::PembayaranBerhasil->value,
            'changed_by' => $this->admin->account_id,
        ]);
    }

    public function test_admin_mengonfirmasi_pembayaran_tunai_langsung(): void
    {
        $this->travelTo('2026-11-24 10:00:00');

        $rental = Rental::factory()->create([
            'account_id' => $this->customer->account_id,
            'status' => RentalStatus::MenungguPembayaran,
        ]);
        $payment = Payment::factory()->create([
            'rental_id' => $rental->rental_id,
            'type' => PaymentType::Sewa,
            'method' => PaymentMethod::Tunai,
            'status' => PaymentStatus::MenungguPembayaran,
            'expires_at' => now()->addHours(2),
        ]);
        $payment->cash()->create(['payment_code' => 'TNI-1234-5678']);

        $this->actingAs($this->admin)
            ->post(route('admin.transaksi.konfirmasi-pembayaran', $rental))
            ->assertRedirect();

        $this->assertDatabaseHas('payment', [
            'payment_id' => $payment->payment_id,
            'status' => PaymentStatus::Berhasil->value,
            'confirmed_by' => $this->admin->account_id,
        ]);

        $rental->refresh();

        expect($rental->status)->toBe(RentalStatus::PembayaranBerhasil)
            ->and($rental->booking_code)->toStartWith('BK-')
            ->and($rental->invoice_number)->toBe('INV-2026-0001');
    }

    public function test_admin_tidak_dapat_mengonfirmasi_qris_yang_belum_dibayar(): void
    {
        $rental = Rental::factory()->create([
            'account_id' => $this->customer->account_id,
            'status' => RentalStatus::MenungguPembayaran,
        ]);
        $payment = Payment::factory()->create([
            'rental_id' => $rental->rental_id,
            'method' => PaymentMethod::QRIS,
            'status' => PaymentStatus::MenungguPembayaran,
        ]);

        $this->actingAs($this->admin)
            ->post(route('admin.transaksi.konfirmasi-pembayaran', $rental))
            ->assertConflict();

        $this->assertDatabaseHas('rental', [
            'rental_id' => $rental->rental_id,
            'status' => RentalStatus::MenungguPembayaran->value,
        ]);
        $this->assertDatabaseHas('payment', [
            'payment_id' => $payment->payment_id,
            'status' => PaymentStatus::MenungguPembayaran->value,
        ]);
        $this->assertDatabaseCount('booking_history', 0);
    }

    public function test_admin_menyelesaikan_pengembalian_tepat_waktu_tanpa_denda(): void
    {
        $this->travelTo('2026-11-24 10:00:00');

        $mobil = Car::factory()->create(['status' => CarStatus::Disewa]);
        $rental = Rental::factory()->create([
            'account_id' => $this->customer->account_id,
            'car_id' => $mobil->car_id,
            'status' => RentalStatus::Berjalan,
            'end_date' => now()->addDay(),
        ]);

        $this->actingAs($this->admin)
            ->post(route('admin.transaksi.selesaikan', $rental))
            ->assertRedirect();

        $this->assertDatabaseHas('rental', [
            'rental_id' => $rental->rental_id,
            'status' => RentalStatus::Selesai->value,
            'late_fee' => 0,
            'returned_at' => '2026-11-24 10:00:00',
        ]);
        $this->assertSame(CarStatus::Tersedia->value, $mobil->fresh()->status);
        $this->assertDatabaseCount('payment', 0);
        $this->assertDatabaseHas('booking_history', [
            'rental_id' => $rental->rental_id,
            'status' => RentalStatus::Selesai->value,
        ]);
    }

    public function test_admin_menyelesaikan_pengembalian_terlambat_dengan_payment_denda(): void
    {
        $this->travelTo('2026-11-24 10:00:00');

        $mobil = Car::factory()->create(['status' => CarStatus::Disewa]);
        $rental = Rental::factory()->create([
            'account_id' => $this->customer->account_id,
            'car_id' => $mobil->car_id,
            'status' => RentalStatus::Terlambat,
            'end_date' => now()->subMinutes(61),
            'price_per_day' => 500000,
        ]);

        $this->actingAs($this->admin)
            ->post(route('admin.transaksi.selesaikan', $rental), [
                'konfirmasi' => 1,
                'late_fee' => 1,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('rental', [
            'rental_id' => $rental->rental_id,
            'status' => RentalStatus::Selesai->value,
            'late_fee' => 60000,
            'returned_at' => '2026-11-24 10:00:00',
        ]);
        $this->assertDatabaseHas('payment', [
            'rental_id' => $rental->rental_id,
            'type' => PaymentType::Denda->value,
            'method' => PaymentMethod::Tunai->value,
            'amount' => 60000,
            'status' => PaymentStatus::Berhasil->value,
            'confirmed_by' => $this->admin->account_id,
        ]);
        $this->assertSame(CarStatus::Tersedia->value, $mobil->fresh()->status);
    }

    public function test_admin_harus_mengisi_denda_untuk_pengembalian_terlambat(): void
    {
        $rental = Rental::factory()->create([
            'account_id' => $this->customer->account_id,
            'status' => RentalStatus::Terlambat,
            'end_date' => now()->subDay(),
        ]);

        $this->actingAs($this->admin)
            ->post(route('admin.transaksi.selesaikan', $rental))
            ->assertSessionHasErrors('konfirmasi');

        $this->assertDatabaseHas('rental', [
            'rental_id' => $rental->rental_id,
            'status' => RentalStatus::Terlambat->value,
        ]);
        $this->assertDatabaseCount('payment', 0);
    }

    public function test_admin_menyelesaikan_pengembalian_terlambat_tanpa_denda(): void
    {
        $this->travelTo('2026-11-24 10:00:00');

        $mobil = Car::factory()->create(['status' => CarStatus::Disewa]);
        $rental = Rental::factory()->create([
            'account_id' => $this->customer->account_id,
            'car_id' => $mobil->car_id,
            'status' => RentalStatus::Terlambat,
            'end_date' => '2026-11-23 23:59:59',
            'price_per_day' => 500000,
        ]);

        $this->actingAs($this->admin)
            ->post(route('admin.transaksi.selesaikan', $rental), ['tanpa_denda' => 1])
            ->assertRedirect()
            ->assertSessionHas('success', 'Transaksi berhasil diselesaikan.');

        $this->assertDatabaseHas('rental', [
            'rental_id' => $rental->rental_id,
            'status' => RentalStatus::Selesai->value,
            'late_fee' => 0,
            'returned_at' => '2026-11-23 23:59:59',
        ]);
        $this->assertDatabaseCount('payment', 0);
        $this->assertDatabaseHas('booking_history', [
            'rental_id' => $rental->rental_id,
            'status' => RentalStatus::Selesai->value,
            'note' => 'Dikembalikan tepat waktu (tanpa denda)',
        ]);
        $this->assertSame(CarStatus::Tersedia->value, $mobil->fresh()->status);
    }

    public function test_admin_tidak_boleh_memilih_denda_dan_tanpa_denda_sekaligus(): void
    {
        $rental = Rental::factory()->create([
            'account_id' => $this->customer->account_id,
            'status' => RentalStatus::Terlambat,
            'end_date' => now()->subDay(),
        ]);

        $this->actingAs($this->admin)
            ->post(route('admin.transaksi.selesaikan', $rental), [
                'konfirmasi' => 1,
                'tanpa_denda' => 1,
            ])
            ->assertSessionHasErrors([
                'konfirmasi' => 'Pilih salah satu cara penyelesaian.',
                'tanpa_denda' => 'Pilih salah satu cara penyelesaian.',
            ]);

        $this->assertDatabaseHas('rental', [
            'rental_id' => $rental->rental_id,
            'status' => RentalStatus::Terlambat->value,
        ]);
        $this->assertDatabaseCount('payment', 0);
    }

    public function test_pengembalian_tepat_waktu_tetap_mencatat_waktu_sekarang(): void
    {
        $this->travelTo('2026-11-24 10:00:00');

        $rental = Rental::factory()->create([
            'account_id' => $this->customer->account_id,
            'status' => RentalStatus::Berjalan,
            'end_date' => now()->addDay(),
        ]);

        $this->actingAs($this->admin)
            ->post(route('admin.transaksi.selesaikan', $rental), ['tanpa_denda' => 1])
            ->assertRedirect();

        $this->assertDatabaseHas('rental', [
            'rental_id' => $rental->rental_id,
            'status' => RentalStatus::Selesai->value,
            'late_fee' => 0,
            'returned_at' => '2026-11-24 10:00:00',
        ]);
        $this->assertDatabaseCount('payment', 0);
    }

    public function test_detail_transaksi_terlambat_menyediakan_tombol_selesaikan_tanpa_denda(): void
    {
        $this->travelTo('2026-11-24 10:00:00');

        $rental = Rental::factory()->create([
            'account_id' => $this->customer->account_id,
            'status' => RentalStatus::Terlambat,
            'end_date' => '2026-11-23 23:59:59',
            'price_per_day' => 500000,
        ]);

        $this->actingAs($this->admin)
            ->get(route('admin.transaksi.show', $rental))
            ->assertOk()
            ->assertSee('Pengembalian Terlambat')
            ->assertSee('Selesaikan Tanpa Denda')
            ->assertSee('name="tanpa_denda"', false);
    }

    public function test_admin_membatalkan_pesanan_dengan_alasan_wajib(): void
    {
        $this->travelTo('2026-11-24 10:00:00');

        $rental = Rental::factory()->create([
            'account_id' => $this->customer->account_id,
            'status' => RentalStatus::MenungguVerifikasi,
        ]);
        $payment = Payment::factory()->create([
            'rental_id' => $rental->rental_id,
            'status' => PaymentStatus::MenungguKonfirmasi,
        ]);

        $this->actingAs($this->admin)
            ->post(route('admin.transaksi.batalkan', $rental), ['cancel_reason' => 'Data tidak sesuai'])
            ->assertRedirect();

        $this->assertDatabaseHas('rental', [
            'rental_id' => $rental->rental_id,
            'status' => RentalStatus::Dibatalkan->value,
            'cancel_reason' => 'Data tidak sesuai',
            'cancelled_at' => '2026-11-24 10:00:00',
        ]);
        $this->assertDatabaseHas('payment', [
            'payment_id' => $payment->payment_id,
            'status' => PaymentStatus::Kadaluarsa->value,
        ]);
        $this->assertDatabaseHas('booking_history', [
            'rental_id' => $rental->rental_id,
            'status' => RentalStatus::Dibatalkan->value,
            'changed_by' => $this->admin->account_id,
        ]);
    }

    public function test_admin_tidak_dapat_membatalkan_pesanan_tanpa_alasan(): void
    {
        $rental = Rental::factory()->create([
            'account_id' => $this->customer->account_id,
            'status' => RentalStatus::MenungguVerifikasi,
        ]);

        $this->actingAs($this->admin)
            ->post(route('admin.transaksi.batalkan', $rental))
            ->assertSessionHasErrors('cancel_reason');

        $this->assertDatabaseHas('rental', [
            'rental_id' => $rental->rental_id,
            'status' => RentalStatus::MenungguVerifikasi->value,
        ]);
    }

    public function test_customer_detail_pesanan_dapat_diakses_oleh_pemilik(): void
    {
        $mobil = Car::factory()->create(['merk' => 'Toyota', 'nama_mobil' => 'Avanza']);

        $rental = Rental::factory()->create([
            'account_id' => $this->customer->account_id,
            'car_id' => $mobil->car_id,
            'booking_code' => 'BK-TEST99',
            'status' => RentalStatus::Berjalan,
        ]);
        AddOn::create([
            'rental_id' => $rental->rental_id,
            'handover_method' => 'Ambil di garasi',
            'driver_fee' => 0,
        ]);

        $response = $this->actingAs($this->customer)->get(route('customer.detail-pesanan', ['id' => $rental->rental_id]));
        $response->assertOk();
        $response->assertSee('BK-TEST99');
        $response->assertSee('Detail Pesanan');
    }

    public function test_detail_riwayat_menampilkan_riwayat_status_dari_booking_history(): void
    {
        $rental = Rental::factory()->create([
            'account_id' => $this->customer->account_id,
            'status' => RentalStatus::Selesai,
        ]);

        BookingHistory::factory()->for($rental, 'rental')->create([
            'sequence_no' => 1,
            'status' => RentalStatus::MenungguPembayaran,
            'note' => 'Pesanan dibuat melalui aplikasi',
        ]);
        BookingHistory::factory()->for($rental, 'rental')->create([
            'sequence_no' => 2,
            'status' => RentalStatus::PembayaranBerhasil,
            'note' => 'Pembayaran dikonfirmasi admin',
        ]);

        $this->actingAs($this->admin)
            ->get(route('admin.riwayat-transaksi.show', $rental))
            ->assertOk()
            ->assertSee('Riwayat Status')
            ->assertSee('Pesanan dibuat melalui aplikasi')
            ->assertSee('Pembayaran dikonfirmasi admin');
    }

    public function test_customer_detail_menampilkan_metode_dan_tautan_pembayaran_sebelum_konfirmasi(): void
    {
        $rental = Rental::factory()->create([
            'account_id' => $this->customer->account_id,
            'status' => RentalStatus::MenungguPembayaran,
            'booking_code' => null,
            'invoice_number' => null,
        ]);
        $payment = Payment::factory()->create([
            'rental_id' => $rental->rental_id,
            'type' => PaymentType::Sewa,
            'method' => PaymentMethod::QRIS,
            'status' => PaymentStatus::MenungguPembayaran,
        ]);
        Qris::factory()->for($payment, 'payment')->create();

        $this->actingAs($this->customer)
            ->get(route('customer.detail-pesanan', ['id' => $rental->rental_id]))
            ->assertOk()
            ->assertSee('Menunggu konfirmasi pembayaran')
            ->assertSee(PaymentMethod::QRIS->value)
            ->assertSee(route('customer.pembayaran-qris', ['id' => $rental->rental_id]), false)
            ->assertSee('Lihat Pembayaran');
    }
}
