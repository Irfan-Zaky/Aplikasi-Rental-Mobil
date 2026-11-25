<?php

namespace Tests\Feature;

use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\PaymentType;
use App\Enums\RentalStatus;
use App\Models\Account;
use App\Models\Car;
use App\Models\Payment;
use App\Models\Rental;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_beranda_menampilkan_ringkasan_data_mobil(): void
    {
        $this->authenticateAsAdmin();

        Car::factory()->create(['status' => 'Tersedia']);
        Car::factory()->create(['status' => 'Tersedia']);
        Car::factory()->create(['status' => 'Disewa']);

        $response = $this->get('/admin/dashboard');

        $response->assertOk()
            ->assertViewHas('totalCar', 3)
            ->assertViewHas('mobilTersedia', 2)
            ->assertViewHas('mobilDisewa', 1)
            ->assertSee('Beranda')
            ->assertSee('Total Car')
            ->assertSee('Tidak ada pesanan yang menunggu konfirmasi')
            ->assertSee(route('admin.data-mobil.index'), false);
    }

    public function test_beranda_menampilkan_daftar_pembayaran_yang_menunggu_tindakan(): void
    {
        $this->authenticateAsAdmin();

        $qris = Rental::factory()->create([
            'status' => RentalStatus::MenungguVerifikasi,
            'customer_name' => 'Pelanggan QRIS',
        ]);
        Payment::factory()->create([
            'rental_id' => $qris->rental_id,
            'type' => PaymentType::Sewa,
            'method' => PaymentMethod::QRIS,
            'status' => PaymentStatus::MenungguKonfirmasi,
        ]);

        $tunai = Rental::factory()->create([
            'status' => RentalStatus::MenungguPembayaran,
            'customer_name' => 'Pelanggan Tunai',
        ]);
        Payment::factory()->create([
            'rental_id' => $tunai->rental_id,
            'type' => PaymentType::Sewa,
            'method' => PaymentMethod::Tunai,
            'status' => PaymentStatus::MenungguPembayaran,
            'expires_at' => now()->addHours(2),
        ]);

        $this->get('/admin/dashboard')
            ->assertOk()
            ->assertViewHas('pesananMenunggu', 2)
            ->assertSee('Pelanggan QRIS')
            ->assertSee('Pelanggan Tunai')
            ->assertSee(RentalStatus::MenungguVerifikasi->value)
            ->assertSee(RentalStatus::MenungguPembayaran->value)
            ->assertDontSee('Tidak ada pesanan yang menunggu konfirmasi');
    }

    private function authenticateAsAdmin(): void
    {
        $this->actingAs(Account::factory()->admin()->create());
    }
}
