@extends('layouts.customer')

@section('title', 'Katalog')

@push('styles')
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <link rel="stylesheet" type="text/css" href="https://npmcdn.com/flatpickr/dist/themes/dark.css">
@endpush

@section('content')
    @php
        $mobils = $mobils ?? collect();
    @endphp

    <nav class="navbar px-3 w-100" style="background-color: white">
        <div class="container-fluid px-2">
            <div class="w-100 px-3 py-1 d-flex align-items-center">
                <h1 class="fw-bold m-0 fs-3" style="color: #0b1d33">Katalog Mobil</h1>
            </div>
        </div>
    </nav>

    <main class="overflow-hidden">
        <section class="py-4">
            <div class="container">
                <div class="row justify-content-between">
                    <div class="col-12 col-md-8 col-lg-6">
                        <div class="p-3 rounded-4 border-0" style="background-color: white; max-width: 480px;">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <div class="d-flex align-items-center gap-2">
                                    <i class="bi bi-calendar-event text-primary fs-5"></i>
                                    <h2 class="h6 fw-bold mb-0 text-dark">Tanggal sewa</h2>
                                </div>

                                <button id="btn-ubah-tanggal" type="button"
                                    class="btn btn-link p-0 text-primary fw-bold text-decoration-none small fs-6">Ubah</button>
                                <input type="text" id="jangkar-kalender" class="d-none">
                            </div>
                            <div class="d-flex align-items-center gap-2">
                                <div class="bg-white rounded-3 p-3 flex-fill">
                                    <p class="text-secondary small mb-1">Mulai</p>
                                    <p id="teks-tgl-mulai" class="fw-bold text-dark mb-0">
                                        {{ $tanggalMulai ?? 'Pilih tanggal' }}</p>
                                </div>
                                <i class="bi bi-arrow-right text-primary fs-5 px-1"></i>
                                <div class="bg-white rounded-3 p-3 flex-fill">
                                    <p class="text-secondary small mb-1">Selesai</p>
                                    <p id="teks-tgl-selesai" class="fw-bold text-dark mb-0">
                                        {{ $tanggalSelesai ?? 'Pilih tanggal' }}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="d-flex align-items-center my-3 gap-2 overflow-x-auto text-nowrap pb-2">
                    <button id="btn-semua" type="button"
                        class="btn btn-dark rounded-pill px-4 py-2 fw-medium">Semua</button>
                    <button id="btn-jenis" type="button"
                        class="btn btn-outline-dark rounded-pill px-4 py-2 fw-medium bg-white">Jenis</button>
                    <button id="btn-merk" type="button"
                        class="btn btn-outline-dark rounded-pill px-4 py-2 fw-medium bg-white">Merk</button>
                    <button id="btn-harga" type="button"
                        class="btn btn-outline-dark rounded-pill px-4 py-2 fw-medium bg-white">Harga</button>
                </div>

                <div class="d-flex justify-content-between align-items-center my-3">
                    <h2 class="fw-bold m-0 fs-5 text-dark">Mobil Tersedia</h2>
                    <p id="jumlah-mobil" class="text-secondary small mb-1">{{ count($mobils) }} mobil</p>
                </div>

                <div id="daftar-mobil">
                    @forelse ($mobils as $mobil)
                        @php
                            $fotoUtama = data_get($mobil, 'fotoUtama.path_foto');
                            $srcFoto = $fotoUtama ? asset('storage/'.$fotoUtama) : asset('img/foto-mobil-kecil.png');
                        @endphp

                        <article class="card border-0 rounded-4 shadow-sm p-3 mb-3 bg-white item-mobil"
                            data-merk="{{ $mobil->merk ?? '-' }}" data-jenis="{{ $mobil->jenis ?? '-' }}"
                            data-harga="{{ $mobil->harga_sewa ?? 0 }}">
                            <a href="{{ url('/customer/detail-mobil') }}?id={{ $mobil->id ?? '' }}"
                                class="text-decoration-none text-dark d-flex align-items-center gap-3">
                                <img src="{{ $srcFoto }}" alt="{{ $mobil->nama_mobil ?? 'Mobil' }}"
                                    class="rounded-3 object-fit-cover" style="width: 110px; height: 80px;" />
                                <div class="flex-grow-1">
                                    <h3 class="h6 fw-bold mb-1" style="color: #0b1d33;">
                                        {{ $mobil->merk ?? '' }} {{ $mobil->nama_mobil ?? '-' }}</h3>
                                    <p class="text-secondary small mb-2" style="color: #0b1d33;">
                                        {{ $mobil->jenis ?? '-' }} &middot; {{ $mobil->spesifikasi ?? '-' }}</p>
                                    <p class="fw-bold mb-2" style="color: #0b1d33;">Rp
                                        {{ number_format($mobil->harga_sewa ?? 0, 0, ',', '.') }} / hari</p>
                                    <span
                                        class="badge rounded-pill bg-success-subtle text-success fw-medium px-3 py-1">{{ $mobil->status ?? 'Tersedia' }}</span>
                                </div>
                                <img src="{{ asset('img/katalog mobil/panah-kanan-bold.svg') }}" alt="" />
                            </a>
                        </article>
                    @empty
                        <article class="card border-0 rounded-4 shadow-sm p-4 mb-3 bg-white text-center text-muted">
                            <p class="mb-0 small">Belum ada mobil tersedia.</p>
                        </article>
                    @endforelse
                </div>
            </div>
        </section>
    </main>

    <div style="height: 100px; background-color: aliceblue"></div>

    <nav class="navbar fixed-bottom bg-white border-top py-2 px-3 shadow-sm">
        <div class="container-fluid d-flex justify-content-around align-items-center">
            <a href="{{ url('/customer/beranda') }}" aria-label="Menuju beranda"
                class="d-flex flex-column align-items-center text-decoration-none py-1 px-3">
                <img src="{{ asset('img/katalog mobil/rumah-biru.svg') }}" alt="Beranda"
                    style="width: 22px; height: 22px; opacity: 0.6;" />
                <span class="small mt-1 text-muted" style="font-size: 0.7rem;">Beranda</span>
            </a>
            <a href="{{ url('/customer/katalog') }}" aria-label="Menuju katalog"
                class="d-flex flex-column align-items-center text-decoration-none py-1 px-3 rounded-pill"
                style="background-color: rgba(255, 107, 53, 0.12);">
                <img src="{{ asset('img/katalog mobil/logo-mobil-oren.svg') }}" alt="Katalog"
                    style="width: 22px; height: 22px;filter: invert(41%) sepia(85%) saturate(1436%) hue-rotate(338deg) brightness(101%) contrast(97%);" />
                <span class="small mt-1 text-muted" style="font-size: 0.7rem;">Katalog</span>
            </a>
            <a href="{{ url('/customer/riwayat') }}" aria-label="Menuju riwayat"
                class="d-flex flex-column align-items-center text-decoration-none py-1 px-3">
                <img src="{{ asset('img/katalog mobil/jam.svg') }}" alt="Riwayat"
                    style="width: 22px; height: 22px; opacity: 0.6;" />
                <span class="small mt-1 text-muted" style="font-size: 0.7rem;">Riwayat</span>
            </a>
            <a href="{{ url('/customer/profil') }}" aria-label="Menuju profil"
                class="d-flex flex-column align-items-center text-decoration-none py-1 px-3">
                <img src="{{ asset('img/katalog mobil/user.svg') }}" alt="Profil"
                    style="width: 22px; height: 22px; opacity: 0.6;" />
                <span class="small mt-1 text-muted" style="font-size: 0.7rem;">Profil</span>
            </a>
        </div>
    </nav>
@endsection

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script src="{{ asset('js/customer/katalog.js') }}"></script>
@endpush
