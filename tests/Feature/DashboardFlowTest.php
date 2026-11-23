<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\Car;
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

    private function authenticateAsAdmin(): void
    {
        $this->actingAs(Account::factory()->admin()->create());
    }
}
