@extends('layouts.customer')

@section('title', 'Pembayaran Tunai')

@section('content')
    <header class="text-center mb-4">
        <h5 class="fw-bold text-dark m-0">Pembayaran Tunai</h5>
    </header>

    <main class="d-flex flex-column gap-3">
        <section class="card rounded-4 border p-4 shadow-sm text-center">
            <span class="text-muted small mb-1">Kode Pembayaran</span>
            <strong class="fs-4 fw-bold text-dark tracking-wide my-1">{{ $kodePembayaran ?? 'TNI-XXXX-XXXX' }}</strong>
            <span class="text-muted small">Tunjukkan kode ini kepada admin</span>
        </section>

        <section class="card rounded-4 border p-3 shadow-sm">
            <div class="d-flex justify-content-between align-items-center mb-1">
                <span class="text-muted small">Total pembayaran</span>
                <strong id="tunai-total" class="fw-bold text-dark">{{ $totalBiaya ?? 'Rp 0' }}</strong>
            </div>
            <hr class="my-2 text-muted opacity-25">
            <div class="d-flex justify-content-between align-items-center">
                <div class="d-flex align-items-center gap-2">
                    <img src="{{ asset('img/tunai/clock-3.svg') }}" alt="clock" width="18" height="18" />
                    <span class="text-warning-emphasis small fw-medium">Batas waktu 3 jam</span>
                </div>
                <strong class="text-warning-emphasis fw-bold">{{ $sisaWaktu ?? '02:59:59' }}</strong>
            </div>
        </section>

        <section class="card rounded-4 border p-4 shadow-sm">
            <h6 class="fw-bold mb-3 text-dark">Instruksi</h6>
            <ol class="list-unstyled text-muted small mb-0 d-flex flex-column gap-2">
                <li>1. Datang ke garasi sebelum kode kedaluwarsa.</li>
                <li>2. Tunjukkan kode pembayaran kepada admin.</li>
                <li>3. Bayar tunai sesuai total pembayaran.</li>
            </ol>
        </section>

        <a href="https://maps.google.com/?q=Garasi+RodaKita+Batam" class="text-decoration-none">
            <div class="card rounded-4 border p-3 shadow-sm text-dark">
                <span class="text-muted small fw-semibold mb-2 d-block">Lokasi Garasi</span>
                <div class="d-flex align-items-center justify-content-between">
                    <div class="d-flex align-items-center gap-3">
                        <div class="p-2 rounded-circle bg-warning bg-opacity-15">
                            <img src="{{ asset('img/tunai/map-pin.svg') }}" alt="map" width="22"
                                height="22" />
                        </div>
                        <div>
                            <strong class="d-block fs-6 text-dark">Garasi Gorental Batam</strong>
                            <small class="text-muted">Jl. Raja Haji Fisabilillah No. 28</small>
                        </div>
                    </div>
                    <span class="text-muted fs-5 fw-bold">&rsaquo;</span>
                </div>
            </div>
        </a>

        <div class="mt-2">
            <a href="{{ url('/customer/katalog') }}"
                class="btn btn-outline-dark w-100 py-3 rounded-pill fw-bold text-decoration-none d-flex align-items-center justify-content-center"
                style="border-color: #0b1d33; color: #0b1d33;">
                Kembali ke Dashboard
            </a>
        </div>
    </main>
@endsection
