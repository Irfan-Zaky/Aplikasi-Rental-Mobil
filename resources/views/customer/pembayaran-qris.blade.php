@extends('layouts.customer')

@section('title', 'Pembayaran QRIS')

@section('content')
    <header class="text-center mb-4">
        <h5 class="fw-bold text-dark m-0">Pembayaran QRIS</h5>
    </header>

    <main class="d-flex flex-column gap-3">
        <section
            class="alert bg-warning bg-opacity-10 border border-warning border-opacity-25 rounded-4 p-3 d-flex align-items-center justify-content-between mb-0"
            role="alert">
            <div class="d-flex align-items-center gap-2">
                <img src="{{ asset('img/qris/clock-3.svg') }}" alt="clock" width="20" height="20" />
                <span class="text-warning-emphasis fw-medium small">Sisa waktu pembayaran</span>
            </div>
            <strong class="text-warning-emphasis fw-bold">{{ $sisaWaktu ?? '09:59' }}</strong>
        </section>

        <section class="card rounded-4 border p-4 shadow-sm text-center">
            <div class="d-flex justify-content-center mb-3">
                <img src="{{ asset('img/qris/QR code.svg') }}" alt="Kode QR pembayaran" class="img-fluid"
                    style="width: 220px; height: 220px;" />
            </div>
            <p class="text-muted small mb-0">Scan dengan aplikasi pembayaran</p>
        </section>

        <section class="text-center my-2">
            <p class="text-muted small mb-1">Nominal pembayaran</p>
            <h3 id="qris-total" class="fw-bold text-dark mb-2">{{ $totalBiaya ?? 'Rp 0' }}</h3>
            <span class="badge bg-warning bg-opacity-25 text-warning-emphasis rounded-pill px-3 py-2 fw-semibold">Menunggu
                Pembayaran</span>
        </section>

        <section class="card rounded-4 border p-4 shadow-sm">
            <h6 class="fw-bold mb-3 text-dark">Instruksi</h6>
            <ul class="list-unstyled text-muted small mb-0 d-flex flex-column gap-2">
                <li>1. Buka aplikasi pembayaran atau e-wallet.</li>
                <li>2. Pilih menu Scan QR lalu arahkan kamera ke kode.</li>
                <li>3. Pastikan nominal sesuai, kemudian bayar.</li>
            </ul>
        </section>

        <div class="mt-3">
            <a href="{{ url('/customer/katalog') }}"
                class="btn btn-outline-dark w-100 py-3 rounded-pill fw-bold text-decoration-none d-flex align-items-center justify-content-center"
                style="border-color: #0b1d33; color: #0b1d33;">
                Kembali ke Dashboard
            </a>
        </div>
    </main>
@endsection
