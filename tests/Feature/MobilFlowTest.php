<?php

namespace Tests\Feature;

use App\Models\FotoMobil;
use App\Models\Mobil;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class MobilFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_halaman_data_mobil_memiliki_tautan_tambah_mobil(): void
    {
        $response = $this->get('/admin/data-mobil');

        $response->assertOk()
            ->assertSee('Tambah Mobil')
            ->assertSee(route('admin.data-mobil.create'), false);
    }

    public function test_pencarian_keyword_dan_filter_menampilkan_hanya_mobil_yang_cocok(): void
    {
        Mobil::factory()->create([
            'merk' => 'Toyota',
            'nama_mobil' => 'Avanza',
            'jenis' => 'MPV',
            'status' => 'Tersedia',
            'nomor_polisi' => 'B 1111 AAA',
        ]);
        Mobil::factory()->create([
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
        $response = $this->get('/admin/data-mobil/create');

        $response->assertOk()
            ->assertSee('Simpan Kendaraan')
            ->assertSee(route('admin.data-mobil.store'), false)
            ->assertSee('enctype="multipart/form-data"', false);
    }

    public function test_payload_valid_menyimpan_mobil_beserta_foto_utama(): void
    {
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

        $mobil = Mobil::firstOrFail();
        $fotoUtama = $mobil->fotos()->where('is_utama', true)->firstOrFail();

        $response->assertRedirect('/admin/data-mobil');
        $this->assertDatabaseHas('mobils', [
            'id' => $mobil->id,
            'merk' => 'Toyota',
            'nama_mobil' => 'Avanza',
            'jenis' => 'MPV',
            'status' => 'Tersedia',
            'harga_sewa' => 525000,
            'nomor_polisi' => 'B 1234 ABC',
        ]);
        $fotoPertama = $mobil->fotos()->orderBy('id')->firstOrFail();

        $this->assertSame(2, $mobil->fotos()->count());
        $this->assertNotSame($fotoPertama->path_foto, $fotoUtama->path_foto);
        Storage::disk('public')->assertExists($fotoUtama->path_foto);
    }

    public function test_halaman_detail_dan_edit_menampilkan_data_mobil(): void
    {
        $mobil = Mobil::factory()->create();
        FotoMobil::factory()->create([
            'mobil_id' => $mobil->id,
            'is_utama' => true,
        ]);

        $this->get(route('admin.data-mobil.show', $mobil))
            ->assertOk()
            ->assertSee($mobil->nama_mobil)
            ->assertSee($mobil->kode_mobil);

        $this->get(route('admin.data-mobil.edit', $mobil))
            ->assertOk()
            ->assertSee($mobil->nama_mobil)
            ->assertSee(route('admin.data-mobil.update', $mobil), false);
    }

    public function test_hapus_mobil_menghapus_data_dan_file_fotonya(): void
    {
        Storage::fake('public');
        $mobil = Mobil::factory()->create();
        $foto = FotoMobil::factory()->create(['mobil_id' => $mobil->id]);
        Storage::disk('public')->put($foto->path_foto, 'isi foto');

        $response = $this->delete(route('admin.data-mobil.destroy', $mobil));

        $response->assertRedirect('/admin/data-mobil');
        $this->assertDatabaseMissing('mobils', ['id' => $mobil->id]);
        $this->assertDatabaseMissing('mobil_fotos', ['id' => $foto->id]);
        Storage::disk('public')->assertMissing($foto->path_foto);
    }

    public function test_payload_kosong_ditolak_dan_tidak_menyimpan_data(): void
    {
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
        $this->assertSame(0, Mobil::count());
        Storage::disk('public')->assertEmpty();
    }
}
