@extends('layouts.customer')

@section('title', 'Riwayat Pesanan')

@section('content')
    @php
        $riwayat = $riwayat ?? collect();
    @endphp

    <nav class="navbar px-3 w-100" style="background-color: white;">
        <div class="container-fluid px-2">
            <div class="w-100 px-3 py-1 d-flex align-items-center justify-content-center">
                <h1 class="fw-bold m-0 fs-3" style="color:#0b1d33">Riwayat Pesanan</h1>
            </div>
        </div>
    </nav>

    <main>
        <section class="py-4">
            <div class="container">
                <div class="row justify-content-center">
                    <div class="col-12 col-md-10 col-lg-11">
                        <div class="d-flex align-items-center container my-2 pb-3 px-3 gap-2">
                            <button type="button"
                                class="btn btn-dark rounded-pill px-4 py-2 fw-medium">Semua</button>
                            <button type="button"
                                class="btn btn-dark rounded-pill px-4 py-2 fw-medium">Aktif</button>
                            <button type="button"
                                class="btn btn-dark rounded-pill px-4 py-2 fw-medium">Selesai</button>
                        </div>

                        <div id="daftar-riwayat-customer">
                            @forelse ($riwayat as $pesanan)
                                @php
                                    $statusRiwayat = data_get($pesanan, 'status', '-');
                                    $warnaBadge = 'bg-warning-subtle text-warning-emphasis';

                                    if ($statusRiwayat === 'Selesai') {
                                        $warnaBadge = 'bg-secondary-subtle text-secondary';
                                    } elseif (in_array($statusRiwayat, ['Berhasil', 'Pembayaran Berhasil'], true)) {
                                        $warnaBadge = 'bg-success-subtle text-success';
                                    }
                                @endphp

                                <article class="card border-0 shadow-sm rounded-4 p-3 mb-3 bg-white">
                                    <a href="{{ url('/customer/detail-pesanan') }}?id={{ data_get($pesanan, 'id') }}"
                                        class="text-decoration-none text-dark">
                                        <div class="d-flex align-items-center justify-content-between mb-3">
                                            <div class="d-flex align-items-center gap-3">
                                                <img src="{{ asset('img/foto-mobil-kecil.png') }}"
                                                    alt="{{ data_get($pesanan, 'nama_mobil', 'Mobil') }}"
                                                    class="rounded-3 object-fit-cover" width="90" height="65" />
                                                <div>
                                                    <h3 class="fw-bold fs-6 mb-1 text-dark">
                                                        {{ data_get($pesanan, 'nama_mobil', '-') }}</h3>
                                                    <p class="text-muted small mb-1">
                                                        {{ data_get($pesanan, 'tgl_sewa', '-') }}</p>
                                                    <p class="fw-bold text-dark mb-0">
                                                        {{ data_get($pesanan, 'total_biaya', 'Rp 0') }}</p>
                                                </div>
                                            </div>
                                            <div>
                                                <span class="text-muted fs-5">&rsaquo;</span>
                                            </div>
                                        </div>
                                        <div class="d-flex align-items-center justify-content-between pt-2 border-top">
                                            <span
                                                class="badge rounded-pill {{ $warnaBadge }} px-3 py-2 fw-medium">{{ $statusRiwayat }}</span>
                                            <span class="text-primary fw-medium small">Lihat detail</span>
                                        </div>
                                    </a>
                                </article>
                            @empty
                                <div class="text-center text-muted py-5 bg-white rounded-4 shadow-sm">
                                    <p class="mb-0">Belum ada riwayat pesanan.</p>
                                    <small class="text-muted">Silakan sewa mobil melalui katalog.</small>
                                </div>
                            @endforelse
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </main>

    <nav class="navbar fixed-bottom bg-white border-top py-2 px-3 shadow-sm">
        <div class="container-fluid d-flex justify-content-around align-items-center">
            <a href="{{ url('/customer/beranda') }}" aria-label="Menuju beranda"
                class="d-flex flex-column align-items-center text-decoration-none py-1 px-3">
                <img src="{{ asset('img/riwayaat pesaanan/rumah-biru.svg') }}" alt="Beranda"
                    style="width: 22px; height: 22px; opacity: 0.6;" />
                <span class="small mt-1 text-muted" style="font-size: 0.7rem;">Beranda</span>
            </a>
            <a href="{{ url('/customer/katalog') }}" aria-label="Menuju katalog"
                class="d-flex flex-column align-items-center text-decoration-none py-1 px-3">
                <img src="{{ asset('img/riwayaat pesaanan/logo-mobil-biru.svg') }}" alt="Katalog"
                    style="width: 22px; height: 22px; opacity: 0.6;" />
                <span class="small mt-1 text-muted" style="font-size: 0.7rem;">Katalog</span>
            </a>
            <a href="{{ url('/customer/riwayat') }}" aria-label="Menuju riwayat"
                class="d-flex flex-column align-items-center text-decoration-none py-1 px-3 rounded-pill"
                style="background-color: rgba(255, 107, 53, 0.12);">
                <img src="{{ asset('img/riwayaat pesaanan/jam-oren.svg') }}" alt="Riwayat"
                    style="width: 22px; height: 22px; filter: invert(41%) sepia(85%) saturate(1436%) hue-rotate(338deg) brightness(101%) contrast(97%);" />
                <span class="small mt-1 text-muted" style="font-size: 0.7rem;">Riwayat</span>
            </a>
            <a href="{{ url('/customer/profil') }}" aria-label="Menuju profil"
                class="d-flex flex-column align-items-center text-decoration-none py-1 px-3">
                <img src="{{ asset('img/riwayaat pesaanan/user.svg') }}" alt="Profil"
                    style="width: 22px; height: 22px; opacity: 0.6;" />
                <span class="small mt-1 text-muted" style="font-size: 0.7rem;">Profil</span>
            </a>
        </div>
    </nav>
@endsection
