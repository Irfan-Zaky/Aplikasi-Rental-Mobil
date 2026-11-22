<?php

namespace Tests\Feature;

use App\Models\FotoMobil;
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

    public function test_katalog_mengambil_data_dan_foto_utama_dari_database(): void
    {
        $customer = User::factory()->create();
        $mobil = Mobil::factory()->create([
            'merk' => 'MerkUnikDatabase',
            'nama_mobil' => 'ModelUnikDatabase',
            'jenis' => 'SUV',
            'status' => 'Tersedia',
            'harga_sewa' => 725000,
            'spesifikasi' => 'Spesifikasi Unik Database',
        ]);
        FotoMobil::create([
            'mobil_id' => $mobil->id,
            'path_foto' => 'mobil-fotos/foto-unik-database.jpg',
            'is_utama' => true,
        ]);

        $this->actingAs($customer)->get(route('customer.katalog'))
            ->assertOk()
            ->assertSee('MerkUnikDatabase ModelUnikDatabase')
            ->assertSee('Spesifikasi Unik Database')
            ->assertSee('725.000')
            ->assertSee('mobil-fotos/foto-unik-database.jpg')
            ->assertSee('1 mobil')
            ->assertDontSee('Toyota Avanza');
    }

    public function test_detail_mobil_menampilkan_carousel_banyak_foto_dengan_foto_utama_di_urutan_pertama(): void
    {
        $customer = User::factory()->create();
        $mobil = Mobil::factory()->create([
            'merk' => 'Toyota',
            'nama_mobil' => 'Avanza Carousel',
        ]);

        // Foto 1: Bukan utama
        FotoMobil::create([
            'mobil_id' => $mobil->id,
            'path_foto' => 'mobil-fotos/foto-samping.jpg',
            'is_utama' => false,
        ]);

        // Foto 2: Utama (favorit)
        FotoMobil::create([
            'mobil_id' => $mobil->id,
            'path_foto' => 'mobil-fotos/foto-favorit-utama.jpg',
            'is_utama' => true,
        ]);

        // Foto 3: Bukan utama
        FotoMobil::create([
            'mobil_id' => $mobil->id,
            'path_foto' => 'mobil-fotos/foto-interior.jpg',
            'is_utama' => false,
        ]);

        // Di halaman katalog, card tetap menampilkan foto utama / favorit
        $this->actingAs($customer)->get(route('customer.katalog'))
            ->assertOk()
            ->assertSee('mobil-fotos/foto-favorit-utama.jpg')
            ->assertDontSee('mobil-fotos/foto-samping.jpg');

        // Di halaman detail mobil, carousel memuat semua foto
        $respDetail = $this->actingAs($customer)->get(route('customer.detail-mobil', $mobil));
        $respDetail->assertOk();
        $respDetail->assertSee('carSlider');
        $respDetail->assertSee('mobil-fotos/foto-favorit-utama.jpg');
        $respDetail->assertSee('mobil-fotos/foto-samping.jpg');
        $respDetail->assertSee('mobil-fotos/foto-interior.jpg');
        $respDetail->assertSee('★ Utama');
    }

    public function test_katalog_pencarian_kata_kunci(): void
    {
        $customer = User::factory()->create();
        Mobil::factory()->create(['merk' => 'Toyota', 'nama_mobil' => 'Avanza Veloz']);
        Mobil::factory()->create(['merk' => 'Honda', 'nama_mobil' => 'Civic Turbo']);

        $response = $this->actingAs($customer)->get(route('customer.katalog', ['keyword' => 'Veloz']));
        $response->assertOk();
        $response->assertSee('Avanza Veloz');
        $response->assertDontSee('Civic Turbo');
    }

    public function test_katalog_filter_merk_menampilkan_pilihan_dropdown_dan_dapat_difilter(): void
    {
        $customer = User::factory()->create();
        Mobil::factory()->create(['merk' => 'Daihatsu', 'nama_mobil' => 'Rocky']);
        Mobil::factory()->create(['merk' => 'Mitsubishi', 'nama_mobil' => 'Xpander']);

        // Verifikasi opsi select berisi merk yang ada di database
        $respKatalog = $this->actingAs($customer)->get(route('customer.katalog'));
        $respKatalog->assertOk();
        $respKatalog->assertSee('<option value="Daihatsu"', false);
        $respKatalog->assertSee('<option value="Mitsubishi"', false);
        $respKatalog->assertSee('modal-filter-merk');
        $respKatalog->assertSee('daftar-merk');
        $respKatalog->assertSee('input-filter-merk');
        $respKatalog->assertSee('list="daftar-merk"', false);

        // Filter dengan merk Daihatsu
        $respFilter = $this->actingAs($customer)->get(route('customer.katalog', ['merk' => 'Daihatsu']));
        $respFilter->assertOk();
        $respFilter->assertSee('Rocky');
        $respFilter->assertDontSee('Xpander');
    }

    public function test_katalog_filter_jenis_menampilkan_pilihan_dan_dapat_difilter(): void
    {
        $customer = User::factory()->create();
        Mobil::factory()->create(['jenis' => 'SUV', 'nama_mobil' => 'Mobil SUV']);
        Mobil::factory()->create(['jenis' => 'Sedan', 'nama_mobil' => 'Mobil Sedan']);

        $respKatalog = $this->actingAs($customer)->get(route('customer.katalog'));
        $respKatalog->assertOk();
        $respKatalog->assertSee('modal-filter-jenis');
        $respKatalog->assertSee('daftar-jenis');
        $respKatalog->assertSee('<option value="SUV"', false);
        $respKatalog->assertSee('<option value="Sedan"', false);

        // Filter jenis SUV
        $respFilter = $this->actingAs($customer)->get(route('customer.katalog', ['jenis' => 'SUV']));
        $respFilter->assertOk();
        $respFilter->assertSee('Mobil SUV');
        $respFilter->assertDontSee('Mobil Sedan');
    }

    public function test_katalog_filter_rentang_harga_min_dan_max(): void
    {
        $customer = User::factory()->create();
        Mobil::factory()->create(['nama_mobil' => 'Mobil Murah', 'harga_sewa' => 250000]);
        Mobil::factory()->create(['nama_mobil' => 'Mobil Sedang', 'harga_sewa' => 500000]);
        Mobil::factory()->create(['nama_mobil' => 'Mobil Mahal', 'harga_sewa' => 1000000]);

        $response = $this->actingAs($customer)->get(route('customer.katalog', [
            'harga_min' => 400000,
            'harga_max' => 600000,
        ]));
        $response->assertOk();
        $response->assertSee('modal-filter-harga');
        $response->assertSee('Batas Terendah (Rp)');
        $response->assertSee('Batas Tertinggi (Rp)');
        $response->assertSee('Mobil Sedang');
        $response->assertDontSee('Mobil Murah');
        $response->assertDontSee('Mobil Mahal');
    }

    public function test_katalog_dapat_menggabungkan_semua_filter_dalam_satu_form(): void
    {
        $customer = User::factory()->create();
        Mobil::factory()->create([
            'merk' => 'Toyota', 'nama_mobil' => 'Avanza Cocok', 'jenis' => 'SUV', 'harga_sewa' => 500000,
        ]);
        Mobil::factory()->create([
            'merk' => 'Toyota', 'nama_mobil' => 'Agya MPs', 'jenis' => 'MPV', 'harga_sewa' => 500000,
        ]);
        Mobil::factory()->create([
            'merk' => 'Honda', 'nama_mobil' => 'BR-V Cocok', 'jenis' => 'SUV', 'harga_sewa' => 500000,
        ]);
        Mobil::factory()->create([
            'merk' => 'Toyota', 'nama_mobil' => 'Fortuner Mahal', 'jenis' => 'SUV', 'harga_sewa' => 900000,
        ]);

        $response = $this->actingAs($customer)->get(route('customer.katalog', [
            'keyword' => 'Cocok',
            'merk' => 'Toyota',
            'jenis' => 'SUV',
            'harga_min' => 400000,
            'harga_max' => 600000,
        ]));

        $response->assertOk()
            ->assertSee('form-filter-katalog')
            ->assertSee('Avanza Cocok')
            ->assertDontSee('Agya MPs')
            ->assertDontSee('BR-V Cocok')
            ->assertDontSee('Fortuner Mahal');
    }

    public function test_katalog_menampilkan_semua_mobil_tanpa_paginasi(): void
    {
        $customer = User::factory()->create();
        $mobils = Mobil::factory()->count(12)->create();

        $response = $this->actingAs($customer)->get(route('customer.katalog'));

        $response->assertOk()->assertSee('12 mobil');
        foreach ($mobils as $mobil) {
            $response->assertSee($mobil->nama_mobil);
        }
    }

    public function test_katalog_tombol_semua_menampilkan_seluruh_mobil(): void
    {
        $customer = User::factory()->create();
        Mobil::factory()->create(['nama_mobil' => 'Mobil A']);
        Mobil::factory()->create(['nama_mobil' => 'Mobil B']);

        $response = $this->actingAs($customer)->get(route('customer.katalog'));
        $response->assertOk();
        $response->assertSee('Mobil A');
        $response->assertSee('Mobil B');
        $response->assertSee('btn-semua');
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
