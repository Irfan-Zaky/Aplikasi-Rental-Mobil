<?php

namespace Tests\Feature;

use App\Enums\RentalStatus;
use App\Models\Account;
use App\Models\Car;
use App\Models\CarPhoto;
use App\Models\Rental;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CarFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_halaman_data_mobil_memiliki_tautan_tambah_mobil(): void
    {
        $this->authenticateAsAdmin();

        $response = $this->get('/admin/data-mobil');

        $response->assertOk()
            ->assertSee('Tambah Car')
            ->assertSee(route('admin.data-mobil.create'), false);
    }

    public function test_pencarian_keyword_dan_filter_menampilkan_hanya_mobil_yang_cocok(): void
    {
        $this->authenticateAsAdmin();

        Car::factory()->create([
            'merk' => 'Toyota',
            'nama_mobil' => 'Avanza',
            'jenis' => 'MPV',
            'status' => 'Tersedia',
            'nomor_polisi' => 'B 1111 AAA',
        ]);
        Car::factory()->create([
            'merk' => 'Daihatsu',
            'nama_mobil' => 'Xenia',
            'jenis' => 'MPV',
            'status' => 'Tersedia',
            'nomor_polisi' => 'B 2222 BBB',
        ]);

        $response = $this->get('/admin/data-mobil?keyword=Avanza&jenis=MPV&status=Tersedia');

        $response->assertOk()
            ->assertSee('Avanza')
            ->assertDontSee('Xenia');
    }

    public function test_halaman_tambah_mobil_menampilkan_form_simpan_kendaraan(): void
    {
        $this->authenticateAsAdmin();

        $response = $this->get('/admin/data-mobil/create');

        $response->assertOk()
            ->assertSee('Simpan Kendaraan')
            ->assertSee(route('admin.data-mobil.store'), false)
            ->assertSee('enctype="multipart/form-data"', false);
    }

    public function test_payload_valid_menyimpan_mobil_beserta_foto_utama(): void
    {
        $this->authenticateAsAdmin();
        Storage::fake('public');

        $response = $this->post('/admin/data-mobil', [
            'merk' => 'Toyota',
            'nama' => 'Avanza',
            'jenis' => 'MPV',
            'status' => 'Tersedia',
            'harga' => 525000,
            'plat' => 'B 1234 ABC',
            'spesifikasi' => 'Otomatis, 7 penumpang, bensin.',
            'foto_utama' => '1',
            'fotos' => [
                UploadedFile::fake()->image('depan.jpg', 20, 20),
                UploadedFile::fake()->image('belakang.jpg', 20, 20),
            ],
        ]);

        $mobil = Car::firstOrFail();
        $fotoUtama = $mobil->fotos()->where('is_utama', true)->firstOrFail();

        $response->assertRedirect('/admin/data-mobil');
        $this->assertDatabaseHas('car', [
            'car_id' => $mobil->car_id,
            'merk' => 'Toyota',
            'nama_mobil' => 'Avanza',
            'jenis' => 'MPV',
            'status' => 'Tersedia',
            'harga_sewa' => 525000,
            'nomor_polisi' => 'B 1234 ABC',
        ]);
        $fotoPertama = $mobil->fotos()->orderBy('photo_id')->firstOrFail();

        $this->assertSame(2, $mobil->fotos()->count());
        $this->assertNotSame($fotoPertama->path_foto, $fotoUtama->path_foto);
        Storage::disk('public')->assertExists($fotoUtama->path_foto);
    }

    public function test_halaman_detail_dan_edit_menampilkan_data_mobil(): void
    {
        $this->authenticateAsAdmin();

        $mobil = Car::factory()->create();
        CarPhoto::factory()->create([
            'car_id' => $mobil->car_id,
            'is_utama' => true,
        ]);

        $this->get(route('admin.data-mobil.show', $mobil))
            ->assertOk()
            ->assertSee($mobil->nama_mobil)
            ->assertSee($mobil->kode_mobil);

        $this->get(route('admin.data-mobil.edit', $mobil))
            ->assertOk()
            ->assertSee($mobil->nama_mobil)
            ->assertSee(route('admin.data-mobil.update', $mobil), false)
            ->assertSee('id="foto-utama-lama"', false)
            ->assertSee('id="foto-lama-container"', false);
    }

    public function test_edit_mobil_mengganti_foto_utama_di_antara_foto_lama(): void
    {
        $this->authenticateAsAdmin();

        $mobil = Car::factory()->create();
        $fotoPertama = CarPhoto::factory()->for($mobil)->create(['is_utama' => true]);
        $fotoKedua = CarPhoto::factory()->for($mobil)->create(['is_utama' => false]);

        $response = $this->put(route('admin.data-mobil.update', $mobil), $this->payloadCar($mobil) + [
            'foto_lama' => [$fotoPertama->photo_id, $fotoKedua->photo_id],
            'foto_utama_lama' => $fotoKedua->photo_id,
        ]);

        $response->assertRedirect('/admin/data-mobil');
        $this->assertDatabaseHas('car_photo', ['photo_id' => $fotoPertama->photo_id, 'is_utama' => false]);
        $this->assertDatabaseHas('car_photo', ['photo_id' => $fotoKedua->photo_id, 'is_utama' => true]);
    }

    public function test_edit_mobil_tanpa_foto_utama_lama_tetap_mempertahankan_foto_utama(): void
    {
        $this->authenticateAsAdmin();

        $mobil = Car::factory()->create();
        $fotoPertama = CarPhoto::factory()->for($mobil)->create(['is_utama' => false]);
        $fotoFavorit = CarPhoto::factory()->for($mobil)->create(['is_utama' => true]);

        $response = $this->put(route('admin.data-mobil.update', $mobil), $this->payloadCar($mobil) + [
            'foto_lama' => [$fotoPertama->photo_id, $fotoFavorit->photo_id],
        ]);

        $response->assertRedirect('/admin/data-mobil');
        $this->assertDatabaseHas('car_photo', ['photo_id' => $fotoPertama->photo_id, 'is_utama' => false]);
        $this->assertDatabaseHas('car_photo', ['photo_id' => $fotoFavorit->photo_id, 'is_utama' => true]);
    }

    public function test_edit_mobil_menyimpan_foto_lama_sebagai_utama_saat_foto_baru_diunggah(): void
    {
        $this->authenticateAsAdmin();
        Storage::fake('public');

        $mobil = Car::factory()->create();
        $fotoLama = CarPhoto::factory()->for($mobil)->create(['is_utama' => true]);

        $response = $this->put(route('admin.data-mobil.update', $mobil), $this->payloadCar($mobil) + [
            'foto_lama' => [$fotoLama->photo_id],
            'foto_utama_lama' => $fotoLama->photo_id,
            'foto_utama' => '1',
            'fotos' => [
                UploadedFile::fake()->image('depan.jpg', 20, 20),
                UploadedFile::fake()->image('belakang.jpg', 20, 20),
            ],
        ]);

        $response->assertRedirect('/admin/data-mobil');
        $this->assertDatabaseHas('car_photo', ['photo_id' => $fotoLama->photo_id, 'is_utama' => true]);
        $this->assertSame(3, $mobil->fotos()->count());
        $mobil->fotos()->where('photo_id', '!=', $fotoLama->photo_id)->each(function (CarPhoto $foto): void {
            $this->assertDatabaseHas('car_photo', ['photo_id' => $foto->photo_id, 'is_utama' => false]);
        });
    }

    public function test_edit_mobil_memilih_foto_utama_dari_foto_yang_baru_diunggah(): void
    {
        $this->authenticateAsAdmin();
        Storage::fake('public');

        $mobil = Car::factory()->create();

        $response = $this->put(route('admin.data-mobil.update', $mobil), $this->payloadCar($mobil) + [
            'foto_lama' => [''],
            'foto_utama' => '1',
            'fotos' => [
                UploadedFile::fake()->image('depan.jpg', 20, 20),
                UploadedFile::fake()->image('belakang.jpg', 20, 20),
            ],
        ]);

        $response->assertRedirect('/admin/data-mobil');
        $fotos = $mobil->fotos()->orderBy('photo_id')->get();
        $this->assertCount(2, $fotos);
        $this->assertDatabaseHas('car_photo', ['photo_id' => $fotos[0]->photo_id, 'is_utama' => false]);
        $this->assertDatabaseHas('car_photo', ['photo_id' => $fotos[1]->photo_id, 'is_utama' => true]);
    }

    public function test_edit_mobil_menghapus_foto_lama_yang_tidak_lagi_ada_di_modal(): void
    {
        $this->authenticateAsAdmin();

        $mobil = Car::factory()->create();
        $fotoDihapus = CarPhoto::factory()->for($mobil)->create(['is_utama' => true]);
        $fotoDitambah = CarPhoto::factory()->for($mobil)->create(['is_utama' => false]);

        $response = $this->put(route('admin.data-mobil.update', $mobil), $this->payloadCar($mobil) + [
            'foto_lama' => [$fotoDitambah->photo_id],
            'foto_utama_lama' => $fotoDitambah->photo_id,
        ]);

        $response->assertRedirect('/admin/data-mobil');
        $this->assertDatabaseMissing('car_photo', ['photo_id' => $fotoDihapus->photo_id]);
        $this->assertDatabaseHas('car_photo', ['photo_id' => $fotoDitambah->photo_id, 'is_utama' => true]);
    }

    public function test_edit_mobil_tanpa_parameter_foto_mempertahankan_foto_utama(): void
    {
        $this->authenticateAsAdmin();

        $mobil = Car::factory()->create();
        $fotoUtama = CarPhoto::factory()->for($mobil)->create(['is_utama' => true]);

        $this->put(route('admin.data-mobil.update', $mobil), $this->payloadCar($mobil))
            ->assertRedirect('/admin/data-mobil');

        $this->assertDatabaseHas('car_photo', ['photo_id' => $fotoUtama->photo_id, 'is_utama' => true]);
    }

    public function test_edit_mobil_menolak_foto_utama_dari_mobil_lain(): void
    {
        $this->authenticateAsAdmin();

        $mobil = Car::factory()->create();
        $fotoCarLain = CarPhoto::factory()->create(['is_utama' => true]);

        $this->put(route('admin.data-mobil.update', $mobil), $this->payloadCar($mobil) + [
            'foto_lama' => [$fotoCarLain->photo_id],
            'foto_utama_lama' => $fotoCarLain->photo_id,
        ])->assertSessionHasErrors(['foto_lama.0', 'foto_utama_lama']);
    }

    public function test_hapus_mobil_tanpa_transaksi_aktif_melakukan_soft_delete(): void
    {
        $this->authenticateAsAdmin();
        Storage::fake('public');

        $mobil = Car::factory()->create();
        $foto = CarPhoto::factory()->create(['car_id' => $mobil->car_id]);
        Storage::disk('public')->put($foto->path_foto, 'isi foto');

        $response = $this->delete(route('admin.data-mobil.destroy', $mobil));

        $response->assertRedirect('/admin/data-mobil');
        $this->assertSoftDeleted('car', ['car_id' => $mobil->car_id]);
        $this->assertDatabaseHas('car_photo', ['photo_id' => $foto->photo_id]);
        Storage::disk('public')->assertExists($foto->path_foto);
        $this->assertDatabaseMissing('car', ['car_id' => $mobil->car_id, 'deleted_at' => null]);
    }

    public function test_hapus_mobil_dengan_transaksi_aktif_menjadikannya_nonaktif(): void
    {
        $this->authenticateAsAdmin();

        $mobil = Car::factory()->create(['status' => 'Tersedia']);
        Rental::factory()->create([
            'car_id' => $mobil->car_id,
            'status' => RentalStatus::MenungguVerifikasi,
        ]);

        $this->delete(route('admin.data-mobil.destroy', $mobil))
            ->assertRedirect('/admin/data-mobil');

        $this->assertDatabaseHas('car', [
            'car_id' => $mobil->car_id,
            'status' => 'Nonaktif',
            'deleted_at' => null,
        ]);
    }

    public function test_payload_kosong_ditolak_dan_tidak_menyimpan_data(): void
    {
        $this->authenticateAsAdmin();
        Storage::fake('public');

        $response = $this->from('/admin/data-mobil/create')
            ->post('/admin/data-mobil', []);

        $response->assertRedirect('/admin/data-mobil/create')
            ->assertSessionHasErrors([
                'merk',
                'nama',
                'jenis',
                'status',
                'harga',
                'plat',
                'spesifikasi',
                'fotos',
            ]);
        $this->assertSame(0, Car::count());
        Storage::disk('public')->assertEmpty();
    }

    /**
     * @return array<string, mixed>
     */
    private function payloadCar(Car $mobil): array
    {
        return [
            'merk' => $mobil->merk,
            'nama' => $mobil->nama_mobil,
            'jenis' => $mobil->jenis,
            'status' => 'Tersedia',
            'harga' => $mobil->harga_sewa,
            'plat' => $mobil->nomor_polisi,
            'spesifikasi' => $mobil->spesifikasi,
        ];
    }

    private function authenticateAsAdmin(): void
    {
        $this->actingAs(Account::factory()->admin()->create());
    }
}
