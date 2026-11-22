@extends('layouts.customer')

@section('title', 'Detail Mobil')

@section('content')
    @php
        $mobil = $mobil ?? null;
        $fotoMobil = $mobil?->fotoUtama?->path_foto;
    @endphp

    <nav class="navbar bg-white py-3 border-bottom">
        <div class="container-fluid position-relative d-flex justify-content-center align-items-center px-3">
            <a id="btn-kembali" href="{{ url('/customer/katalog') }}" aria-label="Kembali ke Katalog"
                class="position-absolute start-0 ms-3 d-flex align-items-center justify-content-center rounded-circle bg-light text-decoration-none text-dark fw-bold fs-3 pb-1"
                style="width: 40px; height: 40px; color: #0b1d33;">&lsaquo;</a>
            <h2 class="fs-3 fw-bold m-0" style="color: #0b1d33;">Detail Mobil</h2>
        </div>
    </nav>

    <main>
        <section class="card border-0 shadow-sm p-3 px-3 rounded-4">
            <div id="carSlider" class="carousel slide mb-3" data-bs-ride="carousel">
                <div class="carousel-inner rounded-4 overflow-hidden bg-light" style="height: 260px;">
                    <div class="carousel-item active h-100">
                        <img id="img-mobil"
                            src="{{ $fotoMobil ? asset('storage/'.$fotoMobil) : asset('img/foto-mobil-besar.jpg') }}"
                            class="d-block w-100 h-100 object-fit-contain" alt="Mobil">
                    </div>
                </div>
            </div>

            <div class="card-body p-0">
                <div class="d-flex align-items-center justify-content-between mb-1">
                    <h2 id="nama-mobil" class="card-title fs-4 fw-bold text-dark mb-0">
                        {{ $mobil ? $mobil->merk.' '.$mobil->nama_mobil : '-' }}
                    </h2>
                    <span class="badge bg-success bg-opacity-10 text-success px-3 py-2 rounded-pill fw-semibold">
                        {{ $mobil?->status ?? 'tersedia' }}
                    </span>
                </div>

                <p id="kategori-mobil" class="text-muted small mb-3">
                    {{ $mobil?->jenis ?? '-' }} &middot; {{ $mobil?->spesifikasi ?? '-' }}
                </p>
                <p id="harga-mobil" class="text-dark fs-5 fw-bold">
                    {{ $mobil ? 'Rp '.number_format($mobil->harga_sewa, 0, ',', '.') : 'Rp -' }} / hari
                </p>
            </div>
        </section>

        <section class="m-0 p-0 min-vh-100 pb-5">
            <h2 class="px-3 mb-3 mt-3 fw-bold h3">Spesifikasi</h2>
            <p class="px-3 mt-1 h6 my-2">
                <span class="fw-bold h6">Kapasitas Penumpang : <span id="spec-kursi"
                        class="text-muted h6">{{ $mobil?->kursi ?? '-' }}</span></span><br>
                <span class="fw-bold h6">Transmisi : <span id="spec-transmisi"
                        class="text-muted h6">{{ $mobil?->transmisi ?? '-' }}</span></span><br>
                <span class="fw-bold h6">Bahan Bakar : <span id="spec-bbm"
                        class="text-muted h6">{{ $mobil?->bbm ?? '-' }}</span></span><br>
                <span class="fw-bold h6">Kapasitas Mesin : <span id="spec-mesin"
                        class="text-muted h6">{{ $mobil?->mesin ?? '-' }}</span></span><br>
                <span class="fw-bold h6">Fitur Kenyamanan : <span id="spec-kenyamanan"
                        class="text-muted h6">{{ $mobil?->kenyamanan ?? '-' }}</span></span><br>
                <span class="fw-bold h6">Fitur Keamanan : <span id="spec-keamanan"
                        class="text-muted h6">{{ $mobil?->keamanan ?? '-' }}</span></span>
                <br><br>
                <span id="deskripsi-mobil" class="h6">{{ $mobil?->deskripsi ?? $mobil?->spesifikasi ?? '-' }}</span>
            </p>
        </section>

        <form id="form-pilih-mobil" action="{{ route('customer.pilih-tanggal') }}" method="get">
            <input type="hidden" name="id" id="input-id-mobil" value="{{ $mobil?->id ?? '' }}">
            <div class="fixed-bottom bg-white py-3 px-3 border-top shadow-sm">
                <div class="container p-0">
                    <button type="submit"
                        class="btn text-white w-100 py-3 rounded-1 fw-bold d-flex align-items-center justify-content-center gap-2"
                        style="background-color: #0b1d33;"> Pilih Mobil
                        <img src="{{ asset('img/detail mobil/panah-kanan.svg') }}" alt="lanjut" width="18"
                            height="18" />
                    </button>
                </div>
            </div>
        </form>
    </main>
@endsection
