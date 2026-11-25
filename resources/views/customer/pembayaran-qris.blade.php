@extends('layouts.customer')

@section('title', 'Pembayaran QRIS')

@section('content')
    <header class="text-center mb-4">
        <h5 class="fw-bold text-dark m-0">Pembayaran QRIS</h5>
    </header>

    <main
        id="qris-payment"
        class="d-flex flex-column gap-3"
        data-status-url="{{ route('customer.pembayaran-qris.status', ['id' => $rentalId]) }}"
        data-dashboard-url="{{ route('customer.beranda') }}"
        data-expires-at="{{ $waktuBerakhir ?? '' }}"
        data-paid="{{ $pembayaranDiterima ? 'true' : 'false' }}"
    >
        <section id="qris-sukses"
            class="card rounded-4 border p-4 shadow-sm text-center @unless($pembayaranDiterima) d-none @endunless">
            <div class="d-flex justify-content-center mb-3">
                <span class="d-flex align-items-center justify-content-center rounded-circle bg-success bg-opacity-10"
                    style="width: 72px; height: 72px;">
                    <span class="text-success fw-bold fs-3">✓</span>
                </span>
            </div>
            <h6 class="fw-bold text-dark mb-2">Pembayaran diterima</h6>
            <p class="text-muted small mb-3">
                Pembayaran QRIS berhasil diterima. Status pesanan masih menunggu verifikasi admin.
            </p>
            <p class="text-muted small mb-3">Anda akan diarahkan ke dashboard secara otomatis.</p>
            <a href="{{ route('customer.beranda') }}"
                class="btn btn-outline-dark w-100 py-3 rounded-pill fw-bold text-decoration-none"
                style="border-color: #0b1d33; color: #0b1d33;">
                Ke Dashboard Sekarang
            </a>
        </section>

        <div id="qris-pending" class="d-flex flex-column gap-3 @if($pembayaranDiterima) d-none @endif">
            <section
                class="alert bg-warning bg-opacity-10 border border-warning border-opacity-25 rounded-4 p-3 d-flex align-items-center justify-content-between mb-0"
                role="alert">
                <div class="d-flex align-items-center gap-2">
                    <img src="{{ asset('img/qris/clock-3.svg') }}" alt="clock" width="20" height="20" />
                    <span class="text-warning-emphasis fw-medium small">Sisa waktu pembayaran</span>
                </div>
                <strong id="qris-timer" class="text-warning-emphasis fw-bold">{{ $sisaWaktu ?? '09:59' }}</strong>
            </section>

            <section class="card rounded-4 border p-4 shadow-sm text-center">
                <div class="d-flex justify-content-center mb-3">
                    <img src="{{ $qrUrl ?: asset('img/qris/QR code.svg') }}" alt="Kode QR pembayaran" class="img-fluid"
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
        </div>
    </main>
@endsection

@push('scripts')
    <script>
        (() => {
            const root = document.getElementById('qris-payment');
            if (!root) {
                return;
            }

            let timerPolling = null;
            let timerHitungMundur = null;
            let dialihkan = false;

            const hentikanTimer = () => {
                if (timerPolling !== null) {
                    window.clearInterval(timerPolling);
                }

                if (timerHitungMundur !== null) {
                    window.clearInterval(timerHitungMundur);
                }
            };

            const tampilkanSukses = () => {
                if (dialihkan) {
                    return;
                }

                dialihkan = true;
                hentikanTimer();

                document.getElementById('qris-pending')?.classList.add('d-none');
                document.getElementById('qris-sukses')?.classList.remove('d-none');

                window.setTimeout(() => {
                    window.location.assign(root.dataset.dashboardUrl);
                }, 2000);
            };

            const elemenTimer = document.getElementById('qris-timer');
            const waktuBerakhir = Date.parse(root.dataset.expiresAt || '');

            if (elemenTimer && Number.isFinite(waktuBerakhir)) {
                const perbaruiHitungMundur = () => {
                    const sisaDetik = Math.max(0, Math.floor((waktuBerakhir - Date.now()) / 1000));
                    const jam = Math.floor(sisaDetik / 3600);
                    const menit = Math.floor((sisaDetik % 3600) / 60);
                    const detik = sisaDetik % 60;

                    elemenTimer.textContent = [jam, menit, detik]
                        .map((nilai) => String(nilai).padStart(2, '0'))
                        .join(':');

                    if (sisaDetik === 0) {
                        window.clearInterval(timerHitungMundur);
                    }
                };

                perbaruiHitungMundur();
                timerHitungMundur = window.setInterval(perbaruiHitungMundur, 1000);
            }

            if (root.dataset.paid === 'true') {
                tampilkanSukses();
                return;
            }

            timerPolling = window.setInterval(async () => {
                try {
                    const response = await fetch(root.dataset.statusUrl, {
                        credentials: 'same-origin',
                        cache: 'no-store',
                        headers: {
                            Accept: 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                    });

                    if (!response.ok) {
                        return;
                    }

                    const data = await response.json();
                    if (data.paid === true) {
                        tampilkanSukses();
                    }
                } catch (error) {
                    // Poll berikutnya akan mencoba lagi bila jaringan sementara gagal.
                }
            }, 3000);
        })();
    </script>
@endpush
