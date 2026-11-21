<?php

use App\Http\Controllers\MobilController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/admin/dashboard');

Route::get('/admin/dashboard', function () {
    return view('dashboard', [
        'title' => 'home',
    ]);
})->name('admin.dashboard');

Route::get('/admin/transaksi', function () {
    return view('transaksi');
})->name('admin.transaksi');

Route::get('/admin/riwayat-transaksi', function () {
    return view('riwayat-transaksi');
})->name('admin.riwayat-transaksi');

Route::get('/admin/data-mobil', [MobilController::class, 'index'])->name('admin.data-mobil.index');
Route::get('/admin/data-mobil/create', [MobilController::class, 'create'])->name('admin.data-mobil.create');
Route::post('/admin/data-mobil', [MobilController::class, 'store'])->name('admin.data-mobil.store');
Route::get('/admin/data-mobil/{mobil}', [MobilController::class, 'show'])->name('admin.data-mobil.show');
Route::get('/admin/data-mobil/{mobil}/edit', [MobilController::class, 'edit'])->name('admin.data-mobil.edit');
Route::put('/admin/data-mobil/{mobil}', [MobilController::class, 'update'])->name('admin.data-mobil.update');
Route::delete('/admin/data-mobil/{mobil}', [MobilController::class, 'destroy'])->name('admin.data-mobil.destroy');
