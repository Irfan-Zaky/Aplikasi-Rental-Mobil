@extends('layouts.customer')

@section('title', 'Beranda')

@section('content')
    @php
        $pesananTerakhir = $pesananTerakhir ?? null;
    @endphp

    <nav class="navbar px-3 w-100" style="background-color: white">
        <div class="container-fluid px-2">
            <div class="w-100 px-3 py-1 d-flex align-items-center">
                <h1 class="fw-bold m-0 fs-3">Beranda</h1>
            </div>
        </div>
    </nav>

    <main style="background-color: aliceblue">
        <section class="px-3 p-3 mt-2">
            <div class="bg-white rounded-4 p-2 shadow-sm d-flex align-items-center justify-content-between">
                <div class="d-flex align-items-center gap-3">
                    <div class="rounded-circle overflow-hidden flex-shrink-0 d-flex align-items-center justify-content-center"
                        style="width: 56px; height: 56px; background-color: aliceblue;">
                        <img src="{{ $fotoProfil ? asset('storage/'.$fotoProfil) : asset('img/dasboard.c/gambar-orang-gede.svg') }}"
                            alt="Foto profil"
                            class="w-100 h-100 {{ $fotoProfil ? 'object-fit-cover' : 'object-fit-contain p-2' }}" />
                    </div>
                    <div>
                        <p class="m-0 text-muted fs-6">Halo,</p>
                        <h2 id="nama-customer" class="fw-bold m-0 fs-5">{{ $namaCustomer ?? 'Nama Customer' }}</h2>
                    </div>
                </div>
                <a href="{{ route('customer.profil') }}" aria-label="Menuju profil"
                    class="text-decoration-none text-dark fs-4 px-2 fw-bold">&gt;</a>
            </div>
        </section>

        <section class="px-3 my-3">
            <div class="rounded-4 p-4 text-dark position-relative overflow-hidden shadow-sm"
                style="background-image: url('{{ asset('img/foto-mobil-landing-page-kiri.png') }}'); background-size: cover; background-position: center; min-height: 550px;">
                <div class="position-relative z-1">
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <div class="rounded-4 p-2 d-flex align-items-center justify-content-center"
                            style="width: 56px; height: 56px; background-color: navy">
                            <img src="{{ asset('img/dasboard.c/info.svg') }}" alt="Info" width="30"
                                height="24" />
                        </div>
                        <h2 class="fw-bold m-0 fs-12" style="color: white">Informasi Layanan</h2>
                    </div>
                    <p class="m-0 fs-5" style="color: white">Sewa mobil lepas kunci atau dengan sopir. Bayar QRIS / Tunai
                    </p>
                </div>
            </div>
        </section>

        <section>
            <h2 class="fw-bold px-3 m-0 fs-5">Ringkasan Pesanan</h2>
            <div class="d-flex gap-3 px-3 my-3">
                <article class="bg-white rounded-4 shadow-sm p-3 text-center flex-fill">
                    <strong id="angka-aktif" class="fs-4 text-warning"
                        style="color: red !important">{{ $jumlahAktif ?? 0 }}</strong>
                    <p class="m-0 text-muted small fw-semibold">Aktif</p>
                </article>

                <article class="bg-white rounded-4 shadow-sm p-3 text-center flex-fill">
                    <strong id="angka-menunggu" class="fs-4 text-warning"
                        style="color: green !important">{{ $jumlahMenunggu ?? 0 }}</strong>
                    <p class="m-0 text-muted small fw-semibold">Menunggu</p>
                </article>

                <article class="bg-white rounded-4 shadow-sm p-3 text-center flex-fill">
                    <strong id="angka-selesai" class="fs-4 text-warning"
                        style="color: blue !important">{{ $jumlahSelesai ?? 0 }}</strong>
                    <p class="m-0 text-muted small fw-semibold">Selesai</p>
                </article>
            </div>
        </section>

        <section class="px-3 my-5">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h2 class="fw-bold m-0 fs-5 text-dark">Pesanan Terakhir</h2>
                <a href="{{ route('customer.riwayat') }}" class="text-decoration-none small fw-semibold"
                    style="color: #0b1d33">Lihat semua</a>
            </div>

            <div id="pesanan-terakhir-container">
                @if ($pesananTerakhir)
                    @php
                        $statusPesanan = data_get($pesananTerakhir, 'status', 'Menunggu');
                        $warnaBadge = 'bg-success-subtle text-success';

                        if ($statusPesanan === 'Selesai') {
                            $warnaBadge = 'bg-secondary-subtle text-secondary';
                        } elseif ($statusPesanan === 'Menunggu') {
                            $warnaBadge = 'bg-warning-subtle text-warning';
                        }
                    @endphp

                    <article class="bg-white rounded-4 shadow-sm p-3 border-0">
                        <div class="d-flex gap-3 align-items-center mb-3">
                            <img src="{{ asset('img/foto-mobil-kecil.png') }}"
                                alt="{{ data_get($pesananTerakhir, 'nama_mobil', 'Mobil') }}"
                                class="rounded-3 object-fit-cover" style="width: 100px; height: 75px" />
                            <div>
                                <h3 class="fw-bold fs-6 mb-1 text-dark">
                                    {{ data_get($pesananTerakhir, 'nama_mobil', '-') }}</h3>
                                <p class="text-muted small mb-2">{{ data_get($pesananTerakhir, 'tgl_sewa', '-') }}</p>
                                <span class="badge px-3 py-1 rounded-pill fw-semibold {{ $warnaBadge }}"
                                    style="font-size: 0.75rem;">{{ $statusPesanan }}</span>
                            </div>
                        </div>
                        <hr class="text-muted opacity-25 my-1" />
                        <div class="d-flex justify-content-between align-items-center pt-1">
                            <span class="text-muted small">{{ data_get($pesananTerakhir, 'total_biaya', 'Rp 0') }}</span>
                            <a href="{{ url('/customer/detail-pesanan') }}?id={{ data_get($pesananTerakhir, 'id') }}"
                                class="text-decoration-none small fw-bold d-inline-flex align-items-center gap-1"
                                style="color: #0b1d33">
                                <span>Lihat Detail</span>
                                <span>&gt;</span>
                            </a>
                        </div>
                    </article>
                @else
                    <article class="bg-white rounded-4 shadow-sm p-3 border-0 text-center text-muted py-4">
                        <p class="mb-0 small">Belum ada pesanan aktif.</p>
                    </article>
                @endif
            </div>
        </section>
    </main>

    <div style="height: 100px; background-color: aliceblue"></div>
    <nav class="navbar fixed-bottom bg-white border-top py-2 px-3 shadow-sm">
        <div class="container-fluid d-flex justify-content-around align-items-center">
            <a href="{{ route('customer.beranda') }}" aria-label="Menuju beranda"
                class="d-flex flex-column align-items-center text-decoration-none py-1 px-3 rounded-pill"
                style="background-color: rgba(255, 107, 53, 0.12)">
                <img src="{{ asset('img/dasboard.c/rumah-oren.svg') }}" alt="Beranda"
                    style="width: 22px; height: 22px; filter: invert(41%) sepia(85%) saturate(1436%) hue-rotate(338deg) brightness(101%) contrast(97%);" />
                <span class="small mt-1 text-muted" style="font-size: 0.7rem">Beranda</span>
            </a>
            <a href="{{ route('customer.katalog') }}" aria-label="Menuju katalog"
                class="d-flex flex-column align-items-center text-decoration-none py-1 px-3">
                <img src="{{ asset('img/dasboard.c/logo-mobil-biru.svg') }}" alt="Katalog"
                    style="width: 22px; height: 22px; opacity: 0.6" />
                <span class="small mt-1 text-muted" style="font-size: 0.7rem">Katalog</span>
            </a>
            <a href="{{ route('customer.riwayat') }}" aria-label="Menuju riwayat"
                class="d-flex flex-column align-items-center text-decoration-none py-1 px-3">
                <img src="{{ asset('img/dasboard.c/jam.svg') }}" alt="Riwayat"
                    style="width: 22px; height: 22px; opacity: 0.6" />
                <span class="small mt-1 text-muted" style="font-size: 0.7rem">Riwayat</span>
            </a>
            <a href="{{ route('customer.profil') }}" aria-label="Menuju profil"
                class="d-flex flex-column align-items-center text-decoration-none py-1 px-3">
                <img src="{{ asset('img/dasboard.c/gambar-orang-kecil.svg') }}" alt="Profil"
                    style="width: 22px; height: 22px; opacity: 0.6" />
                <span class="small mt-1 text-muted" style="font-size: 0.7rem">Profil</span>
            </a>
        </div>
    </nav>
@endsection
