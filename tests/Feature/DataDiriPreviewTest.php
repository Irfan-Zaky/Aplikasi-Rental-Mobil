<?php

use App\Models\Account;
use App\Models\Car;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

beforeEach(function () {
    Storage::fake('local');
});

it('renders the saved documents and their private preview links on the personal data page', function () {
    $customer = Account::factory()->create();
    $mobil = Car::factory()->create(['status' => 'Tersedia']);

    foreach (['verifikasi/ktp/ktp.png', 'verifikasi/sim/sim.png', 'verifikasi/selfie/selfie.png'] as $path) {
        Storage::disk('local')->put($path, File::get(public_path('img_admin/detail-transaksi/Identity document.png')));
    }

    $this->actingAs($customer)
        ->withSession(['pemesanan_draft' => [
            'car_id' => $mobil->car_id,
            'customer' => [
                'foto_ktp' => 'verifikasi/ktp/ktp.png',
                'foto_sim' => 'verifikasi/sim/sim.png',
                'swafoto' => 'verifikasi/selfie/selfie.png',
            ],
        ]])
        ->get(route('customer.data-diri'))
        ->assertOk()
        ->assertSee(route('customer.data-diri.dokumen', ['jenis' => 'ktp']), false)
        ->assertSee(route('customer.data-diri.dokumen', ['jenis' => 'sim']), false)
        ->assertSee(route('customer.data-diri.dokumen', ['jenis' => 'selfie']), false);
});

it('streams a draft document from the private disk to its owner', function (string $jenis, string $path) {
    $customer = Account::factory()->create();
    $mobil = Car::factory()->create(['status' => 'Tersedia']);

    Storage::disk('local')->put($path, File::get(public_path('img_admin/detail-transaksi/Identity document.png')));

    $response = $this->actingAs($customer)
        ->withSession(['pemesanan_draft' => [
            'car_id' => $mobil->car_id,
            'customer' => ['foto_ktp' => $path, 'foto_sim' => $path, 'swafoto' => $path],
        ]])
        ->get(route('customer.data-diri.dokumen', ['jenis' => $jenis]));

    $response->assertOk()->assertHeader('Content-Type', 'image/png');

    expect($response->headers->get('Cache-Control'))->toContain('no-store');
})->with([
    'ktp' => ['ktp', 'verifikasi/ktp/ktp.png'],
    'sim' => ['sim', 'verifikasi/sim/sim.png'],
    'selfie' => ['selfie', 'verifikasi/selfie/selfie.png'],
]);

it('redirects guests away from draft document previews', function () {
    $this->get(route('customer.data-diri.dokumen', ['jenis' => 'ktp']))
        ->assertRedirect(route('customer.login'));
});

it('returns 404 when the draft has no document', function () {
    $customer = Account::factory()->create();

    $this->actingAs($customer)
        ->get(route('customer.data-diri.dokumen', ['jenis' => 'sim']))
        ->assertNotFound();
});

it('returns 404 for an unsupported document type', function () {
    $customer = Account::factory()->create();

    $this->actingAs($customer)
        ->get('/customer/data-diri/dokumen/paspor')
        ->assertNotFound();
});
