<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\Rental;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DokumenVerifikasiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
    }

    public function test_halaman_detail_menautkan_dokumen_asli_dan_bukan_gambar_demo(): void
    {
        $rental = Rental::factory()->create();

        $this->actingAs(Account::factory()->admin()->create())
            ->get(route('admin.transaksi.show', $rental))
            ->assertOk()
            ->assertSee(route('admin.transaksi.dokumen', ['rental' => $rental, 'jenis' => 'ktp']))
            ->assertSee(route('admin.transaksi.dokumen', ['rental' => $rental, 'jenis' => 'sim']))
            ->assertSee(route('admin.transaksi.dokumen', ['rental' => $rental, 'jenis' => 'selfie']))
            ->assertDontSee('Identity document.png')
            ->assertDontSee('Selfie document.png');
    }

    public function test_admin_dapat_melihat_foto_ktp_dan_swafoto_dari_disk_private(): void
    {
        $rental = Rental::factory()->create([
            'customer_ktp_photo' => 'verifikasi/ktp/ktp.jpg',
            'customer_sim_photo' => 'verifikasi/sim/sim.jpg',
            'customer_selfie_photo' => 'verifikasi/selfie/selfie.jpg',
        ]);

        Storage::disk('local')->put(
            $rental->customer_ktp_photo,
            File::get(public_path('img_admin/detail-transaksi/Identity document.png'))
        );
        Storage::disk('local')->put(
            $rental->customer_sim_photo,
            File::get(public_path('img_admin/detail-transaksi/Identity document.png'))
        );
        Storage::disk('local')->put(
            $rental->customer_selfie_photo,
            File::get(public_path('img_admin/detail-transaksi/Selfie document.png'))
        );

        $admin = $this->actingAs(Account::factory()->admin()->create());

        foreach (['ktp', 'sim', 'selfie'] as $jenis) {
            $admin->get(route('admin.transaksi.dokumen', ['rental' => $rental, 'jenis' => $jenis]))
                ->assertOk()
                ->assertHeader('Content-Type', 'image/png');
        }
    }

    public function test_customer_tidak_dapat_membuka_dokumen_transaksi(): void
    {
        $rental = Rental::factory()->create();
        Storage::disk('local')->put($rental->customer_ktp_photo, 'isi dokumen');

        $customer = $this->actingAs(Account::factory()->create());

        foreach (['ktp', 'sim', 'selfie'] as $jenis) {
            $customer->get(route('admin.transaksi.dokumen', ['rental' => $rental, 'jenis' => $jenis]))
                ->assertForbidden();
        }
    }

    public function test_tamu_diarahkan_ke_login_saat_membuka_dokumen(): void
    {
        $rental = Rental::factory()->create();

        $this->get(route('admin.transaksi.dokumen', ['rental' => $rental, 'jenis' => 'ktp']))
            ->assertRedirect(route('login'));
    }

    public function test_jenis_dokumen_tidak_valid_menghasilkan_404(): void
    {
        $rental = Rental::factory()->create();

        $this->actingAs(Account::factory()->admin()->create())
            ->get("/admin/transaksi/{$rental->rental_id}/dokumen/paspor")
            ->assertNotFound();
    }

    public function test_dokumen_yang_tidak_ada_menghasilkan_404(): void
    {
        $rental = Rental::factory()->create([
            'customer_ktp_photo' => 'verifikasi/ktp/tidak-ada.jpg',
        ]);

        $this->actingAs(Account::factory()->admin()->create())
            ->get(route('admin.transaksi.dokumen', ['rental' => $rental, 'jenis' => 'ktp']))
            ->assertNotFound();
    }
}
