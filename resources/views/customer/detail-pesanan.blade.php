@extends('layouts.customer')

@section('title', 'Detail Pesanan')

@section('content')
    @php
        $pesanan = $pesanan ?? null;
        $tahap = (int) data_get($pesanan, 'tahap', 1);
        $ikonBiru = asset('img/detail pesanan/bulat-biru.svg');
        $ikonPutih = asset('img/detail pesanan/bulat-putih.svg');
    @endphp

    <nav class="navbar bg-white py-3 border-bottom">
        <div class="container-fluid position-relative d-flex justify-content-center align-items-center px-3">
            <a href="{{ url('/customer/riwayat') }}" aria-label="Kembali ke Riwayat"
                class="position-absolute start-0 ms-3 d-flex align-items-center justify-content-center rounded-circle bg-light text-decoration-none text-dark fw-bold fs-3 pb-1"
                style="width: 40px; height: 40px; color: #0b1d33;">&lsaquo;</a>
            <h2 class="fs-3 fw-bold m-0" style="color: #0b1d33;">Detail Pesanan</h2>
        </div>
    </nav>

    <main>
        <section aria-label="Tahapan pesanan" class="px-4 py-3 bg-white rounded-4 shadow-sm my-3">
            <ol class="d-flex justify-content-between align-items-center list-unstyled m-0 p-0 position-relative">
                <li class="d-flex flex-column align-items-center text-center position-relative z-1">
                    <img id="step-1-img" src="{{ $ikonBiru }}" alt="Tahap 1" width="16" height="16"
                        class="mb-2" />
                    <span id="step-1-text" class="small fw-bold text-dark" style="font-size: 12px;">Menunggu<br>Pembayaran</span>
                </li>

                <li class="d-flex flex-column align-items-center text-center position-relative z-1">
                    <img id="step-2-img" src="{{ $tahap >= 2 ? $ikonBiru : $ikonPutih }}" alt="Tahap 2" width="16"
                        height="16"
                        class="mb-2 {{ $tahap >= 2 ? '' : 'border border-secondary rounded-circle' }}" />
                    <span id="step-2-text"
                        class="small {{ $tahap >= 2 ? 'fw-bold text-dark' : 'text-muted' }}" style="font-size: 12px;">Pembayaran<br>Berhasil</span>
                </li>

                <li class="d-flex flex-column align-items-center text-center position-relative z-1">
                    <img id="step-3-img" src="{{ $tahap >= 3 ? $ikonBiru : $ikonPutih }}" alt="Tahap 3" width="16"
                        height="16"
                        class="mb-2 {{ $tahap >= 3 ? '' : 'border border-secondary rounded-circle' }}" />
                    <span id="step-3-text"
                        class="small {{ $tahap >= 3 ? 'fw-bold text-dark' : 'text-muted' }}" style="font-size: 12px;">Selesai</span>
                </li>
            </ol>
        </section>

        <section aria-label="Kode booking" class="px-3 p-3 bg-light border rounded-4 my-3">
            <p class="text-muted small mb-1 fw-medium">Kode Booking</p>
            <h5 id="info-booking" class="text-dark fw-bold m-0">{{ data_get($pesanan, 'kode_booking', '-') }}</h5>
            <p class="text-muted small mb-1 mt-3 fw-medium">Nomor Invoice</p>
            <h6 id="info-invoice" class="text-dark fw-bold m-0">{{ data_get($pesanan, 'invoice', '-') }}</h6>
        </section>

        <section aria-label="Informasi Pesanan" class="card border-0 shadow-sm rounded-4 p-3 my-3">
            <div class="d-flex flex-column gap-2 mb-3">
                <div class="d-flex justify-content-between align-items-center">
                    <span class="text-muted small">Car</span>
                    <strong id="info-mobil" class="text-dark">{{ data_get($pesanan, 'nama_mobil', '-') }}</strong>
                </div>

                <div class="d-flex justify-content-between align-items-center">
                    <span class="text-muted small">Tanggal</span>
                    <strong id="info-tanggal" class="text-dark">{{ data_get($pesanan, 'tgl_sewa', '-') }}</strong>
                </div>

                <div class="d-flex justify-content-between align-items-center">
                    <span class="text-muted small">Layanan</span>
                    <strong id="info-layanan" class="text-dark">{{ data_get($pesanan, 'layanan', '-') }}</strong>
                </div>

                <div class="d-flex justify-content-between align-items-center">
                    <span class="text-muted small">Penerimaan</span>
                    <strong id="info-penerimaan" class="text-dark">{{ data_get($pesanan, 'penerimaan', '-') }}</strong>
                </div>

                <div class="d-flex justify-content-between align-items-center">
                    <span class="text-muted small">Nama</span>
                    <strong class="text-dark">{{ data_get($pesanan, 'nama_customer', '-') }}</strong>
                </div>

                <div class="d-flex justify-content-between align-items-center">
                    <span class="text-muted small">Nomor HP</span>
                    <strong class="text-dark">{{ data_get($pesanan, 'nomor_hp', '-') }}</strong>
                </div>
            </div>

            <hr class="text-muted opacity-25 my-2">

            <div class="d-flex flex-column gap-2 pt-1">
                <div class="d-flex justify-content-between align-items-center">
                    <strong class="text-dark">Total</strong>
                    <strong id="info-total" class="text-dark fs-5">{{ data_get($pesanan, 'total_biaya', 'Rp 0') }}</strong>
                </div>

                <div class="d-flex justify-content-between align-items-center">
                    <span class="text-muted small">Status</span>
                    <strong id="info-status" class="text-success">{{ data_get($pesanan, 'status', '-') }}</strong>
                </div>

                <div class="d-flex justify-content-between align-items-center">
                    <span class="text-muted small">Metode</span>
                    <strong id="info-metode" class="text-dark">{{ data_get($pesanan, 'metode', '-') }}</strong>
                </div>

                <div class="d-flex justify-content-between align-items-center">
                    <span class="text-muted small">Status Pembayaran</span>
                    <strong id="info-status-pembayaran" class="text-dark">{{ data_get($pesanan, 'status_pembayaran', '-') }}</strong>
                </div>
            </div>
        </section>

        @if (data_get($pesanan, 'tautan_pembayaran'))
            <div class="container my-3">
                <a href="{{ data_get($pesanan, 'tautan_pembayaran') }}"
                    class="btn btn-outline-primary w-100 py-3 rounded-4 fw-bold">Lihat Pembayaran</a>
            </div>
        @endif

        <div class="container my-4 pb-5 d-grid gap-3">
            <a href="{{ url('/customer/beranda') }}" class="btn btn-outline-dark py-3 rounded-1 fw-bold"
                style="border-color: #0b1d33;">
                Kembali ke Dashboard
            </a>
        </div>
    </main>
@endsection
