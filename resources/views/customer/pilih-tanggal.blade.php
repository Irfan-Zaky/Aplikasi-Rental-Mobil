@extends('layouts.customer')

@section('title', 'Pilih Tanggal')

@section('content')
    @php
        $mobil = $mobil ?? null;
        $fotoMobil = $mobil?->fotoUtama?->path_foto;
    @endphp

    <nav class="navbar bg-white py-3 border-bottom">
        <div class="container-fluid position-relative d-flex justify-content-center align-items-center px-3">
            <a id="btn-kembali" href="{{ url('/customer/detail-mobil') }}?id={{ $mobil?->id ?? '' }}"
                aria-label="Kembali ke Detail Mobil"
                class="position-absolute start-0 ms-3 d-flex align-items-center justify-content-center rounded-circle bg-light text-decoration-none text-dark fw-bold fs-3 pb-1"
                style="width: 40px; height: 40px; color: #0b1d33;">&lsaquo;</a>
            <h2 class="fs-3 fw-bold m-0" style="color: #0b1d33;">Pilih Tanggal</h2>
        </div>
    </nav>

    <main class="d-flex flex-column gap-3 pb-5 mb-5">
        <section class="card rounded-4 border p-3 px-3 mt-3 shadow-sm bg-white">
            <div class="d-flex align-items-center gap-3">
                <img id="img-mobil"
                    src="{{ $fotoMobil ? asset('storage/'.$fotoMobil) : asset('img/foto-mobil-kecil.png') }}"
                    alt="Mobil" class="rounded-3 img-fluid object-fit-cover" style="width: 100px; height: 70px;" />
                <div>
                    <h6 id="nama-mobil" class="fw-bold mb-1 text-dark">
                        {{ $mobil ? $mobil->merk.' '.$mobil->nama_mobil : '-' }}</h6>
                    <p id="kategori-mobil" class="text-muted small mb-1">
                        {{ $mobil?->jenis ?? '-' }} &bull; {{ $mobil?->spesifikasi ?? '-' }}</p>
                    <span class="fw-bold text-dark small"><span id="harga-mobil"
                            data-harga-asli="{{ $mobil?->harga_sewa ?? 0 }}">{{ $mobil ? 'Rp '.number_format($mobil->harga_sewa, 0, ',', '.') : 'Rp -' }}</span>
                        <span class="text-muted fw-normal">/ hari</span></span>
                </div>
            </div>
        </section>

        <section class="row g-2 px-3" aria-label="Tanggal sewa">
            <div class="col-6">
                <label for="tanggal-mulai" class="form-label small text-muted fw-semibold">Tanggal Mulai</label>
                <input type="date" id="tanggal-mulai" name="tanggal-mulai"
                    class="form-control rounded-3 py-2 text-dark bg-white" value="{{ $tanggalMulai ?? '' }}" required />
            </div>
            <div class="col-6">
                <label for="tanggal-selesai" class="form-label small text-muted fw-semibold">Tanggal Selesai</label>
                <input type="date" id="tanggal-selesai" name="tanggal-selesai"
                    class="form-control rounded-3 py-2 text-dark bg-white" value="{{ $tanggalSelesai ?? '' }}" required />
            </div>
        </section>

        <section class="card rounded-4 border px-3 p-3 shadow-sm bg-white" aria-label="Ringkasan sewa">
            <div class="d-flex justify-content-between mb-2 px-3">
                <span class="text-muted small">Durasi sewa</span>
                <strong id="teks-durasi" class="fw-semibold text-dark small">{{ $durasi ?? '-' }}</strong>
            </div>
            <div class="d-flex justify-content-between align-items-center px-3">
                <span class="text-muted small">Estimasi biaya</span>
                <strong id="teks-estimasi" class="fw-bold text-dark">{{ $estimasiBiaya ?? 'Rp 0' }}</strong>
            </div>
        </section>

        <form id="form-lanjut" action="{{ url('/customer/ketersediaan-mobil') }}" method="get">
            <input type="hidden" name="id" value="{{ $mobil?->id ?? '' }}">
            <div class="fixed-bottom bg-white py-3 px-3 border-top shadow-sm">
                <div class="container p-0">
                    <button type="submit"
                        class="btn text-white w-100 py-3 rounded-1 fw-bold d-flex align-items-center justify-content-center gap-2"
                        style="background-color: #0b1d33;"> Cek Ketersediaan
                        <img src="{{ asset('img/pilih tanggal/panah-kanan-bold.svg') }}" alt="lanjut" width="18"
                            height="18" />
                    </button>
                </div>
            </div>
        </form>
    </main>
@endsection

@push('scripts')
    <script src="{{ asset('js/customer/pilih-tanggal.js') }}"></script>
@endpush
