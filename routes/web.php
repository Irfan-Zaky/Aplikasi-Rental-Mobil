<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\CarController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\KatalogController;
use App\Http\Controllers\PemesananController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    if (auth()->check() && auth()->user()->isAdmin()) {
        return redirect()->route('admin.dashboard');
    }

    return view('welcome');
})->name('beranda');

Route::get('/', fn () => redirect()->route('customer.landing'))->name('landing');

Route::get('/katalog', fn () => redirect()->route('customer.katalog'))->name('katalog');

Route::prefix('customer')->name('customer.')->group(function () {
    Route::view('/landing-page', 'customer.landing-page')->name('landing');

    Route::middleware('guest')->group(function () {
        Route::get('/login', [AuthenticatedSessionController::class, 'createCustomer'])->name('login');
        Route::post('/login', [AuthenticatedSessionController::class, 'store'])->name('login.store');
        Route::get('/daftar-akun', [RegisteredUserController::class, 'createCustomer'])->name('register');
        Route::post('/daftar-akun', [RegisteredUserController::class, 'store'])->name('register.store');
    });

    Route::middleware(['auth', 'customer'])->group(function () {
        Route::get('/beranda', [CustomerController::class, 'beranda'])->name('beranda');
        Route::get('/katalog', [KatalogController::class, 'index'])->name('katalog');
        Route::get('/riwayat', [CustomerController::class, 'riwayat'])->name('riwayat');
        Route::get('/detail-pesanan', [CustomerController::class, 'detailPesanan'])->name('detail-pesanan');
        Route::get('/profil', [CustomerController::class, 'profil'])->name('profil');
        Route::get('/edit-profil', [CustomerController::class, 'editProfil'])->name('edit-profil');
        Route::post('/edit-profil', [CustomerController::class, 'updateProfil'])->name('edit-profil.update');

        Route::get('/detail-mobil/{mobil}', [PemesananController::class, 'detailCar'])->name('detail-mobil');
        Route::get('/pilih-tanggal', [PemesananController::class, 'pilihTanggal'])->name('pilih-tanggal');
        Route::get('/ketersediaan-mobil', [PemesananController::class, 'ketersediaanCar'])->name('ketersediaan-mobil');

        Route::get('/syarat-dan-ketentuan', [PemesananController::class, 'syarat'])->name('syarat');
        Route::post('/syarat-dan-ketentuan', [PemesananController::class, 'syaratStore'])->name('syarat.store');

        Route::get('/pilihan-layanan', [PemesananController::class, 'pilihanLayanan'])->name('pilihan-layanan');
        Route::post('/pilihan-layanan', [PemesananController::class, 'pilihanLayananStore'])->name('pilihan-layanan.store');
        Route::get('/detail-layanan-sopir', [PemesananController::class, 'detailLayananSopir'])->name('detail-layanan.sopir');
        Route::get('/detail-layanan-lepas-kunci', [PemesananController::class, 'detailLayananLepasKunci'])->name('detail-layanan.lepas-kunci');
        Route::post('/detail-layanan', [PemesananController::class, 'detailLayananStore'])->name('detail-layanan.store');

        Route::get('/data-diri', [PemesananController::class, 'dataDiri'])->name('data-diri');
        Route::post('/data-diri', [PemesananController::class, 'dataDiriStore'])->name('data-diri.store');

        Route::get('/ringkasan-pemesanan', [PemesananController::class, 'ringkasan'])->name('ringkasan-pemesanan');
        Route::post('/ringkasan-pemesanan', [PemesananController::class, 'ringkasanStore'])->name('ringkasan-pemesanan.store');
        Route::get('/pilihan-pembayaran', [PemesananController::class, 'pilihanPembayaran'])->name('pilihan-pembayaran');
    });
});

Route::middleware(['auth', 'verified', 'admin'])->group(function () {
    Route::get('/dashboard', [AdminController::class, 'index'])->name('dashboard');
});

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

Route::middleware(['auth', 'admin'])->group(function () {
    Route::get('/admin', fn () => redirect()->route('admin.dashboard'))->name('admin.index');

    Route::get('/admin/dashboard', [AdminController::class, 'index'])->name('admin.dashboard');

    Route::get('/admin/transaksi', [AdminController::class, 'transaksi'])->name('admin.transaksi');
    Route::get('/admin/transaksi/{rental}', [AdminController::class, 'showTransaksi'])->name('admin.transaksi.show');
    Route::get('/admin/transaksi/{rental}/dokumen/{jenis}', [AdminController::class, 'dokumen'])
        ->whereIn('jenis', ['ktp', 'selfie'])
        ->name('admin.transaksi.dokumen');

    Route::get('/admin/riwayat-transaksi', [AdminController::class, 'riwayatTransaksi'])->name('admin.riwayat-transaksi');
    Route::get('/admin/riwayat-transaksi/{rental}', [AdminController::class, 'showRiwayatTransaksi'])->name('admin.riwayat-transaksi.show');

    Route::prefix('/admin/data-mobil')->name('admin.data-mobil.')->group(function () {
        Route::get('/', [CarController::class, 'index'])->name('index');
        Route::get('/create', [CarController::class, 'create'])->name('create');
        Route::post('/', [CarController::class, 'store'])->name('store');
        Route::get('/{mobil}', [CarController::class, 'show'])->name('show');
        Route::get('/{mobil}/edit', [CarController::class, 'edit'])->name('edit');
        Route::put('/{mobil}', [CarController::class, 'update'])->name('update');
        Route::delete('/{mobil}', [CarController::class, 'destroy'])->name('destroy');
    });
});

require __DIR__.'/auth.php';
