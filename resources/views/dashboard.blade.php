@extends('layouts.admin', ['active' => 'dashboard'])

@section('title', 'Beranda Admin')

@section('content')
    @include('components.admin-header', [
        'kicker' => 'INFORMASI RENTAL',
        'judul' => 'Beranda',
        'deskripsi' => 'Pantau ringkasan transaksi dan data mobil',
    ])

    <main>
        <section class="container py-4">
            <div class="row g-3">
                <div class="col-md">
                    <div class="card border-0 shadow-sm rounded-4 p-4 bg-white h-100">
                        <h2 class="fw-bold mb-1" style="font-size: 2rem;">{{ $totalCar }}</h2>
                        <p class="text-muted mb-0 small">Total Car</p>
                    </div>
                </div>
                <div class="col-md">
                    <div class="card border-0 shadow-sm rounded-4 p-4 bg-white h-100">
                        <h2 class="fw-bold mb-1" style="font-size: 2rem;">{{ $mobilTersedia }}</h2>
                        <p class="text-muted mb-0 small">Tersedia</p>
                    </div>
                </div>
                <div class="col-md">
                    <div class="card border-0 shadow-sm rounded-4 p-4 bg-white h-100">
                        <h2 class="fw-bold mb-1" style="font-size: 2rem;">{{ $mobilDisewa }}</h2>
                        <p class="text-muted mb-0 small">Disewa</p>
                    </div>
                </div>
                <div class="col-md">
                    <div class="card border-0 shadow-sm rounded-4 p-4 bg-white h-100">
                        <h2 class="fw-bold mb-1" style="font-size: 2rem;">{{ $totalPesanan }}</h2>
                        <p class="text-muted mb-0 small">Total Pesanan</p>
                    </div>
                </div>
                <div class="col-md">
                    <div class="card border-0 shadow-sm rounded-4 p-4 bg-white h-100">
                        <h2 class="fw-bold mb-1" style="font-size: 2rem;">{{ $pesananMenunggu }}</h2>
                        <p class="text-muted mb-0 small">Menunggu Konfirmasi</p>
                    </div>
                </div>
            </div>
        </section>

        <section class="container py-4">
            <div class="card border-0 shadow-sm rounded-4 p-4 bg-white">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h4 class="fw-bold mb-0">Pembayaran Menunggu Konfirmasi</h4>
                    <a href="{{ route('admin.transaksi') }}" class="text-decoration-none fw-semibold" style="color: #ff7a00;">Lihat semua ›</a>
                </div>

                <div class="table-responsive">
                    <table class="table align-middle mb-0">
                        <thead style="background-color: #0d1b2a;">
                            <tr>
                                <th class="py-3 ps-3 border-0 text-white" style="background-color: #0d1b2a; color: white !important;">KODE</th>
                                <th class="py-3 border-0" style="background-color: #0d1b2a; color: white !important;">CUSTOMER</th>
                                <th class="py-3 border-0" style="background-color: #0d1b2a; color: white !important;">MOBIL</th>
                                <th class="py-3 border-0" style="background-color: #0d1b2a; color: white !important;">METODE</th>
                                <th class="py-3 border-0" style="background-color: #0d1b2a; color: white !important;">TOTAL</th>
                                <th class="py-3 pe-3 border-0 text-white" style="background-color: #0d1b2a; color: white !important;">STATUS</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td colspan="6" class="text-center text-muted small py-4">Tidak ada pesanan yang menunggu konfirmasi</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </section>
    </main>
@endsection
