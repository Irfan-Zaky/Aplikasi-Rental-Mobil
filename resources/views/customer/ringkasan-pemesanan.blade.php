@extends('layouts.customer')

@section('title', 'Ringkasan Pemesanan')

@section('content')
    @php
        $ringkasan = $ringkasan ?? [];
        $fotoRingkasan = data_get($ringkasan, 'foto');
        $srcFotoRingkasan = $fotoRingkasan
            ? asset('storage/'.$fotoRingkasan)
            : asset('img/foto-mobil-kecil.png');
    @endphp

    <nav class="navbar bg-white py-3 mb-3 border-bottom">
        <div class="container-fluid position-relative d-flex justify-content-center align-items-center px-3">
            <a id="btn-kembali" href="{{ route('customer.pilihan-layanan') }}"
                class="position-absolute start-0 ms-3 d-flex align-items-center justify-content-center rounded-circle bg-light text-decoration-none text-dark fw-bold fs-3 pb-1"
                style="width: 40px; height: 40px;">&lsaquo;</a>
            <h2 class="fs-3 fw-bold m-0">Ringkasan Pemesanan</h2>
        </div>
    </nav>

    <main>
        <section>
            <div class="card rounded-4 border p-2 shadow-sm mx-auto bg-white" style="max-width: 450px;">
                <div class="d-flex align-items-center gap-3">
                    <img id="ringkasan-img" src="{{ $srcFotoRingkasan }}" alt="Mobil"
                        class="rounded-3 img-fluid object-fit-cover" style="width: 120px; height: 80px;">
                    <div>
                        <h6 id="ringkasan-mobil" class="fw-bold mb-1 text-dark">
                            {{ data_get($ringkasan, 'nama_mobil', '-') }}</h6>
                        <span class="fw-bold fs-6 text-dark" id="ringkasan-harga">
                            {{ data_get($ringkasan, 'harga_mobil', 'Rp 0') }}</span>
                        <span class="text-muted small">/ hari</span>
                    </div>
                </div>
            </div>
        </section>

        <div class="card rounded-4 border p-4 shadow-sm mx-auto mt-3 bg-white" style="max-width: 450px;">
            <section>
                <h6 class="fw-bold mb-3 text-dark">Detail perjalanan</h6>
                <div class="d-flex justify-content-between mb-2">
                    <span class="text-muted">Tanggal</span>
                    <strong id="ringkasan-tanggal" class="fw-semibold text-dark">
                        {{ data_get($ringkasan, 'tanggal', '-') }}</strong>
                </div>
                <div class="d-flex justify-content-between mb-2">
                    <span class="text-muted">Durasi</span>
                    <strong id="ringkasan-durasi" class="fw-semibold text-dark">
                        {{ data_get($ringkasan, 'durasi', '-') }}</strong>
                </div>
                <div class="d-flex justify-content-between mb-2">
                    <span class="text-muted">Layanan</span>
                    <strong id="ringkasan-layanan" class="fw-semibold text-dark">
                        {{ data_get($ringkasan, 'layanan', '-') }}</strong>
                </div>
                <div class="d-flex justify-content-between mb-2" id="baris-penerimaan">
                    <span class="text-muted">Penerimaan</span>
                    <strong id="ringkasan-penerimaan" class="fw-semibold text-dark">
                        {{ data_get($ringkasan, 'penerimaan', '-') }}</strong>
                </div>
                <div class="d-flex justify-content-between">
                    <span class="text-muted">Lokasi</span>
                    <strong id="ringkasan-lokasi" class="fw-semibold text-dark text-end" style="max-width: 60%;">
                        {{ data_get($ringkasan, 'lokasi', '-') }}</strong>
                </div>
            </section>

            <hr class="my-4 text-muted opacity-25">

            <section>
                <h6 class="fw-bold mb-3 text-dark">Data penyewa</h6>
                <div class="d-flex justify-content-between mb-2">
                    <span class="text-muted">Nama</span>
                    <strong class="fw-semibold text-dark">{{ $namaPenyewa ?? '-' }}</strong>
                </div>
                <div class="d-flex justify-content-between mb-2">
                    <span class="text-muted">Nomor HP</span>
                    <strong class="fw-semibold text-dark">{{ $nomorHpPenyewa ?? '-' }}</strong>
                </div>
            </section>
        </div>

        <section class="card rounded-4 border p-4 shadow-sm mx-auto mt-3 bg-white" style="max-width: 450px;">
            <div class="d-flex justify-content-between mb-2">
                <span id="label-sewa-dasar" class="text-muted">
                    Sewa mobil {{ data_get($ringkasan, 'durasi') ? '('.data_get($ringkasan, 'durasi').')' : '' }}</span>
                <strong id="biaya-sewa-dasar" class="fw-semibold text-dark">
                    {{ data_get($ringkasan, 'biaya_sewa', 'Rp 0') }}</strong>
            </div>

            <div class="d-flex justify-content-between mb-2" id="baris-biaya-tambahan">
                <span id="label-biaya-tambahan" class="text-muted">Biaya Tambahan</span>
                <strong id="biaya-tambahan" class="fw-semibold text-dark">
                    {{ data_get($ringkasan, 'biaya_tambahan', 'Rp 0') }}</strong>
            </div>

            <hr class="my-3 text-muted opacity-25">

            <div class="d-flex justify-content-between align-items-center">
                <strong class="fw-bold text-dark">Total Biaya</strong>
                <strong id="total-biaya-akhir" class="fw-bold text-dark fs-5">
                    {{ data_get($ringkasan, 'total_biaya', 'Rp 0') }}</strong>
            </div>
        </section>

        <div class="container my-4 pb-5 d-grid gap-3 mx-auto" style="max-width: 450px;">
            <a id="btn-edit-layanan" href="{{ route('customer.pilihan-layanan') }}"
                class="btn btn-outline-dark py-3 rounded-1 fw-bold" style="border-color: #0b1d33;">
                Kembali / Edit Layanan
            </a>
        </div>

        <form action="{{ route('customer.ringkasan-pemesanan.store') }}" method="POST">
            @csrf
            <div class="fixed-bottom bg-white py-3 px-3 border-top shadow-sm">
                <div class="container p-0 mx-auto" style="max-width: 450px;">
                    <button type="submit"
                        class="btn text-white w-100 py-3 rounded-pill fw-bold d-flex align-items-center justify-content-center gap-2"
                        style="background-color: #0b1d33;"> Konfirmasi Pemesanan
                        <img src="{{ asset('img/ringkasan-transaksi/lanjutkan.svg') }}" alt="lanjut" width="18"
                            height="18" />
                    </button>
                </div>
            </div>
        </form>
    </main>
@endsection
