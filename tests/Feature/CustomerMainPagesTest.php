<?php

namespace Tests\Feature;

use App\Models\Mobil;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class CustomerMainPagesTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array<string, array{string}>
     */
    public static function halamanCustomer(): array
    {
        return [
            'beranda' => ['/customer/beranda'],
            'katalog' => ['/customer/katalog'],
            'riwayat' => ['/customer/riwayat'],
            'profil' => ['/customer/profil'],
        ];
    }

    #[DataProvider('halamanCustomer')]
    public function test_tamu_diarahkan_ke_login_customer(string $url): void
    {
        $this->get($url)->assertRedirect(route('customer.login'));
    }

    #[DataProvider('halamanCustomer')]
    public function test_admin_tidak_dapat_membuka_halaman_customer(string $url): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->get($url)->assertForbidden();
    }

    #[DataProvider('halamanCustomer')]
    public function test_customer_dapat_membuka_empat_halaman_utama(string $url): void
    {
        $customer = User::factory()->create();

        $this->actingAs($customer)->get($url)->assertOk();
    }

    public function test_beranda_menampilkan_nama_customer_yang_login(): void
    {
        $customer = User::factory()->create(['name' => 'Customer Beranda']);

        $this->actingAs($customer)->get(route('customer.beranda'))
            ->assertOk()
            ->assertSee('Customer Beranda');
    }

    public function test_profil_menampilkan_data_customer_yang_login(): void
    {
        $customer = User::factory()->create([
            'name' => 'Customer Profil',
            'email' => 'profil@example.com',
            'no_hp' => '081111111111',
        ]);

        $this->actingAs($customer)->get(route('customer.profil'))
            ->assertOk()
            ->assertSee('Customer Profil')
            ->assertSee('profil@example.com')
            ->assertSee('081111111111')
            ->assertDontSee('Akun Terverifikasi');
    }

    public function test_katalog_menampilkan_semua_mobil(): void
    {
        $customer = User::factory()->create();
        Mobil::factory()->create(['nama_mobil' => 'Avanza Satu']);
        Mobil::factory()->create(['nama_mobil' => 'Brio Dua']);

        $this->actingAs($customer)->get(route('customer.katalog'))
            ->assertOk()
            ->assertSee('Avanza Satu')
            ->assertSee('Brio Dua');
    }

    public function test_route_katalog_lama_mengarah_ke_katalog_customer(): void
    {
        $customer = User::factory()->create();

        $this->actingAs($customer)->get('/katalog')
            ->assertRedirect(route('customer.katalog'));
    }

    public function test_tamu_diarahkan_ke_login_customer_saat_membuka_edit_profil(): void
    {
        $this->get(route('customer.edit-profil'))
            ->assertRedirect(route('customer.login'));
    }

    public function test_admin_tidak_dapat_membuka_form_edit_profil_customer(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->get(route('customer.edit-profil'))
            ->assertForbidden();
    }

    public function test_admin_tidak_dapat_melihat_form_login_customer(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->get(route('customer.login'))
            ->assertRedirect(route('dashboard'));
    }

    public function test_customer_tidak_dapat_melihat_form_login_admin(): void
    {
        $customer = User::factory()->create();

        $this->actingAs($customer)->get(route('login'))
            ->assertRedirect(route('customer.beranda'));
    }

    public function test_customer_login_lewat_form_admin_tetap_diarahkan_ke_beranda_customer(): void
    {
        $customer = User::factory()->create([
            'email' => 'customer@example.com',
            'password' => 'password',
        ]);

        $response = $this->post('/admin/login', [
            'login' => 'customer@example.com',
            'password' => 'password',
        ]);

        $response->assertRedirect(route('customer.beranda'));
        $this->assertAuthenticatedAs($customer);
        $this->get('/admin/dashboard')->assertForbidden();
    }

    public function test_customer_dapat_memperbarui_profilnya(): void
    {
        $customer = User::factory()->create([
            'name' => 'Nama Lama',
            'email' => 'lama@example.com',
            'no_hp' => '081222222222',
        ]);

        $response = $this->actingAs($customer)->post(route('customer.edit-profil.update'), [
            'name' => 'Nama Baru',
            'email' => 'baru@example.com',
            'no_hp' => '081333333333',
        ]);

        $response->assertRedirect(route('customer.profil'));
        $this->assertDatabaseHas('users', [
            'id' => $customer->id,
            'name' => 'Nama Baru',
            'email' => 'baru@example.com',
            'no_hp' => '081333333333',
        ]);
    }

    public function test_customer_dapat_mengganti_password_lewat_profil(): void
    {
        $customer = User::factory()->create(['password' => 'password']);

        $this->actingAs($customer)->post(route('customer.edit-profil.update'), [
            'name' => $customer->name,
            'email' => $customer->email,
            'no_hp' => $customer->no_hp,
            'password' => 'password-baru',
            'password_confirmation' => 'password-baru',
        ])->assertRedirect(route('customer.profil'));

        $this->assertTrue(Hash::check('password-baru', $customer->fresh()->password));
    }

    public function test_profil_menolak_nomor_hp_yang_sudah_dipakai(): void
    {
        User::factory()->create(['no_hp' => '081444444444']);
        $customer = User::factory()->create(['no_hp' => '081555555555']);

        $this->actingAs($customer)->from(route('customer.edit-profil'))
            ->post(route('customer.edit-profil.update'), [
                'name' => $customer->name,
                'email' => $customer->email,
                'no_hp' => '081444444444',
            ])
            ->assertRedirect(route('customer.edit-profil'))
            ->assertSessionHasErrors('no_hp');
    }

    public function test_admin_tidak_dapat_memperbarui_profil_customer(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->post(route('customer.edit-profil.update'), [
            'name' => 'Admin Menyamar',
            'email' => $admin->email,
            'no_hp' => $admin->no_hp,
        ])->assertForbidden();
    }

    public function test_beranda_menampilkan_foto_profil_bulat(): void
    {
        $customer = User::factory()->create(['photo_profil' => 'profil/avatar.jpg']);

        $this->actingAs($customer)->get(route('customer.beranda'))
            ->assertOk()
            ->assertSee('rounded-circle', false)
            ->assertSee('storage/profil/avatar.jpg', false);
    }

    public function test_edit_profil_menampilkan_pratinjau_dan_pilihan_hapus_saat_foto_ada(): void
    {
        $customer = User::factory()->create(['photo_profil' => 'profil/avatar.jpg']);

        $this->actingAs($customer)->get(route('customer.edit-profil'))
            ->assertOk()
            ->assertSee('id="preview-foto-profil"', false)
            ->assertSee('storage/profil/avatar.jpg', false)
            ->assertSee('name="hapus_foto_profil"', false);
    }

    public function test_edit_profil_tidak_menampilkan_pilihan_hapus_saat_belum_ada_foto(): void
    {
        $customer = User::factory()->create(['photo_profil' => null]);

        $this->actingAs($customer)->get(route('customer.edit-profil'))
            ->assertOk()
            ->assertDontSee('name="hapus_foto_profil"', false);
    }

    public function test_customer_dapat_menghapus_foto_profil(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('profil/lama.jpg', 'isi lama');

        $customer = User::factory()->create(['photo_profil' => 'profil/lama.jpg']);

        $this->actingAs($customer)->post(route('customer.edit-profil.update'), [
            'name' => $customer->name,
            'email' => $customer->email,
            'no_hp' => $customer->no_hp,
            'hapus_foto_profil' => 1,
        ])->assertRedirect(route('customer.profil'));

        $this->assertNull($customer->fresh()->photo_profil);
        Storage::disk('public')->assertMissing('profil/lama.jpg');
    }

    public function test_customer_dapat_mengganti_foto_profil_lama(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('profil/lama.jpg', 'isi lama');

        $customer = User::factory()->create(['photo_profil' => 'profil/lama.jpg']);

        $this->actingAs($customer)->post(route('customer.edit-profil.update'), [
            'name' => $customer->name,
            'email' => $customer->email,
            'no_hp' => $customer->no_hp,
            'photo_profil' => UploadedFile::fake()->image('baru.jpg'),
        ])->assertRedirect(route('customer.profil'));

        $pathBaru = $customer->fresh()->photo_profil;

        $this->assertNotNull($pathBaru);
        $this->assertStringStartsWith('profil/', $pathBaru);
        Storage::disk('public')->assertMissing('profil/lama.jpg');
        Storage::disk('public')->assertExists($pathBaru);
    }
}
