<?php

namespace Tests\Feature;

use App\Models\Mobil;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_beranda_menampilkan_ringkasan_data_mobil(): void
    {
        $this->authenticateAsAdmin();

        Mobil::factory()->create(['status' => 'Tersedia']);
        Mobil::factory()->create(['status' => 'Tersedia']);
        Mobil::factory()->create(['status' => 'Disewa']);

        $response = $this->get('/admin/dashboard');

        $response->assertOk()
            ->assertViewHas('totalMobil', 3)
            ->assertViewHas('mobilTersedia', 2)
            ->assertViewHas('mobilDisewa', 1)
            ->assertSee('Beranda')
            ->assertSee('Total Mobil')
            ->assertSee('Tidak ada pesanan yang menunggu konfirmasi')
            ->assertSee(route('admin.data-mobil.index'), false);
    }

    private function authenticateAsAdmin(): void
    {
        $this->actingAs(User::factory()->admin()->create());
    }
}
