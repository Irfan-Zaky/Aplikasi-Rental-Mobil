<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\MobilController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/admin/dashboard');

Route::middleware(['auth', 'verified'])->group(function () {
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

    Route::get('/admin/transaksi', function () {
        return view('transaksi');
    })->name('admin.transaksi');

    Route::get('/admin/riwayat-transaksi', function () {
        return view('riwayat-transaksi');
    })->name('admin.riwayat-transaksi');

    Route::prefix('/admin/data-mobil')->name('admin.data-mobil.')->group(function () {
        Route::get('/', [MobilController::class, 'index'])->name('index');
        Route::get('/create', [MobilController::class, 'create'])->name('create');
        Route::post('/', [MobilController::class, 'store'])->name('store');
        Route::get('/{mobil}', [MobilController::class, 'show'])->name('show');
        Route::get('/{mobil}/edit', [MobilController::class, 'edit'])->name('edit');
        Route::put('/{mobil}', [MobilController::class, 'update'])->name('update');
        Route::delete('/{mobil}', [MobilController::class, 'destroy'])->name('destroy');
    });
});

require __DIR__.'/auth.php';
