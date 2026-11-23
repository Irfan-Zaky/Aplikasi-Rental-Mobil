@extends('layouts.customer')

@section('title', 'Ketersediaan Car')

@section('content')
    @php
        $mobil = $mobil ?? null;
        $queryTanggal = http_build_query([
            'id' => $mobil?->car_id ?? '',
            'tanggal-mulai' => $tanggalMulai ?? '',
            'tanggal-selesai' => $tanggalSelesai ?? '',
        ]);
    @endphp

    <nav class="navbar bg-white py-3 border-bottom">
        <div class="container-fluid position-relative d-flex justify-content-center align-items-center px-3">
            <a id="btn-kembali" href="{{ route('customer.pilih-tanggal').'?'.$queryTanggal }}"
                aria-label="Kembali ke Pilih Tanggal"
                class="position-absolute start-0 ms-3 d-flex align-items-center justify-content-center rounded-circle bg-light text-decoration-none text-dark fw-bold fs-3 pb-1"
                style="width: 40px; height: 40px; color: #0b1d33;">&lsaquo;</a>
            <h2 class="fs-3 fw-bold m-0" style="color: #0b1d33;">Detail Layanan</h2>
        </div>
    </nav>

    <main class="m-0 p-0 min-vh-100 pb-5">
        <section aria-label="Status Ketersediaan"
            class="p-4 px-3 bg-success bg-opacity-10 border border-success border-opacity-25 rounded-4 my-3 d-flex flex-column align-items-center justify-content-center text-center mx-3">
            <div class="bg-white rounded-circle p-3 shadow-sm mb-3 d-flex align-items-center justify-content-center"
                style="width: 60px; height: 60px;">
                <img src="{{ asset('img/ketersediaan/centang-mobil-tersedia.svg') }}" alt="Centang"
                    class="w-75 h-75" />
            </div>
            <h2 class="text-success fs-5 fw-bold mb-1">Car Tersedia</h2>
            <p class="text-success small m-0 opacity-75">Pada tanggal yang Anda pilih</p>
        </section>

        <section aria-label="Status Ketersediaan" class="card border-0 shadow-sm rounded-4 p-3 my-3 mx-3 bg-white">
            <div class="d-flex flex-column gap-2 mb-3">
                <div class="d-flex justify-content-between align-items-center">
                    <span class="text-muted small">Car</span>
                    <strong id="info-mobil"
                        class="text-dark">{{ $mobil ? $mobil->merk.' '.$mobil->nama_mobil : '-' }}</strong>
                </div>

                <div class="d-flex justify-content-between align-items-center">
                    <span class="text-muted small">Tanggal Mulai</span>
                    <strong id="info-mulai" class="text-dark">{{ $tanggalMulai ?? '-' }}</strong>
                </div>

                <div class="d-flex justify-content-between align-items-center">
                    <span class="text-muted small">Tanggal Selesai</span>
                    <strong id="info-selesai" class="text-dark">{{ $tanggalSelesai ?? '-' }}</strong>
                </div>

                <div class="d-flex justify-content-between align-items-center">
                    <span class="text-muted small">Durasi</span>
                    <strong id="info-durasi" class="text-dark">{{ $durasi ?? '-' }}</strong>
                </div>

                <hr class="text-muted opacity-25 my-2">

                <div class="d-flex justify-content-between align-items-center">
                    <strong class="text-dark">Estimasi biaya</strong>
                    <strong id="info-biaya" class="text-dark fs-5">{{ $estimasiBiaya ?? 'Rp 0' }}</strong>
                </div>
            </div>
        </section>

        <div class="p-3 rounded-4 d-flex align-items-center gap-3 mb-4 mx-3" style="background-color: #eef7fc;">
            <img src="{{ asset('img/ketersediaan/shield-check-sebelah-harga-terjangkau.svg') }}" alt="Ikon Enkripsi"
                class="flex-shrink-0" width="24" height="24" />
            <p class="text-secondary small mb-0 fw-medium">Harga transparan dan kendaraan telah melewati inspeksi.</p>
        </div>

        <form action="{{ route('customer.syarat') }}" method="get">
            <input type="hidden" name="id" value="{{ $mobil?->car_id ?? '' }}">
            <input type="hidden" name="tanggal-mulai" value="{{ $tanggalMulai ?? '' }}">
            <input type="hidden" name="tanggal-selesai" value="{{ $tanggalSelesai ?? '' }}">
            <div class="fixed-bottom bg-white py-3 px-3 border-top shadow-sm">
                <div class="container p-0">
                    <button type="submit"
                        class="btn text-white w-100 py-3 rounded-pill fw-bold d-flex align-items-center justify-content-center gap-2"
                        style="background-color: #0b1d33;"> Lanjutkan
                        <img src="{{ asset('img/ketersediaan/lanjutkan-icon.svg') }}" alt="lanjutkan" width="18"
                            height="18" />
                    </button>
                </div>
            </div>
        </form>

        <div class="container my-4 pb-5">
            <div class="row g-3">
                <div class="col-6">
                    <a id="btn-ganti-tanggal" href="{{ route('customer.pilih-tanggal').'?'.$queryTanggal }}"
                        class="btn btn-outline-secondary w-100 py-3 rounded-1 fw-bold"
                        style="border-color: #0b1d33; color: #0b1d33;">
                        Ganti Tanggal
                    </a>
                </div>

                <div class="col-6">
                    <a href="{{ url('/customer/katalog') }}"
                        class="btn btn-outline-secondary w-100 py-3 rounded-1 fw-bold"
                        style="border-color: #0b1d33; color: #0b1d33;">
                        Ganti Car
                    </a>
                </div>
            </div>
        </div>
    </main>
@endsection
