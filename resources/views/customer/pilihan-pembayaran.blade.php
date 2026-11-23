@extends('layouts.customer')

@section('title', 'Pilihan Pembayaran')

@section('content')
    @php
        $ringkasan = $ringkasan ?? [];
    @endphp

    <nav class="navbar bg-white py-3 mb-3 border-bottom">
        <div class="container-fluid position-relative d-flex justify-content-center align-items-center px-3">
            <a href="{{ url('/customer/ringkasan-pemesanan') }}" aria-label="Kembali ke ringkasan"
                class="position-absolute start-0 ms-3 d-flex align-items-center justify-content-center rounded-circle bg-light text-decoration-none text-dark fw-bold fs-3 pb-1"
                style="width: 40px; height: 40px; color: #0b1d33;">&lsaquo;</a>
            <h2 class="fs-3 fw-bold m-0" style="color: #0b1d33;">Pembayaran</h2>
        </div>
    </nav>

    <main>
        <section class="card rounded-4 border p-4 shadow-sm mx-auto" style="max-width: 450px;">
            <h6 class="fw-bold mb-3 text-dark">Detail Pesanan</h6>

            <div class="d-flex justify-content-between mb-2">
                <span class="text-muted">Car</span>
                <strong id="bayar-mobil" class="fw-semibold text-dark">
                    {{ data_get($ringkasan, 'nama_mobil', '-') }}</strong>
            </div>

            <div class="d-flex justify-content-between mb-2">
                <span class="text-muted">Tanggal</span>
                <strong id="bayar-tanggal" class="fw-semibold text-dark">
                    {{ data_get($ringkasan, 'tanggal', '-') }}</strong>
            </div>

            <div class="d-flex justify-content-between mb-2">
                <span class="text-muted">Layanan</span>
                <strong id="bayar-layanan" class="fw-semibold text-dark">
                    {{ data_get($ringkasan, 'layanan', '-') }}</strong>
            </div>

            <hr class="my-3 text-muted opacity-25">

            <div class="d-flex justify-content-between align-items-center">
                <strong class="fw-bold text-dark">Total Pembayaran</strong>
                <strong id="bayar-total" class="fw-bold text-dark">
                    {{ data_get($ringkasan, 'total_biaya', 'Rp 0') }}</strong>
            </div>
        </section>

        <section>
            <form id="formPembayaran" data-url-qris="{{ url('/customer/pembayaran-qris') }}"
                data-url-tunai="{{ url('/customer/pembayaran-tunai') }}">
                <h3 class="fw-bold text-dark mb-3 px-3">Pilih metode pembayaran</h3>

                <main class="mb-5 pb-5">
                    <div class="d-flex flex-column gap-3 px-3">
                        <div class="card p-3 rounded-4 shadow-sm border position-relative">
                            <div class="d-flex align-items-center justify-content-between">
                                <label class="stretched-link d-flex align-items-center gap-3 text-decoration-none"
                                    for="qris">
                                    <div class="p-2 rounded-circle bg-warning bg-opacity-10">
                                        <img src="{{ asset('img/option-bayar/qr-code.svg') }}" alt="" width="24"
                                            height="24" />
                                    </div>
                                    <div>
                                        <strong class="d-block fs-6 text-dark">QRIS</strong>
                                        <small class="text-muted">Scan QR, pembayaran diverifikasi otomatis. Berlaku 10
                                            menit.</small>
                                    </div>
                                </label>

                                <div class="form-check z-index-2">
                                    <input class="form-check-input fs-5 cursor-pointer" type="radio"
                                        name="pembayaran" id="qris" value="qris" checked required>
                                </div>
                            </div>
                        </div>

                        <div class="card p-3 rounded-4 shadow-sm border position-relative">
                            <div class="d-flex align-items-center justify-content-between">
                                <label class="stretched-link d-flex align-items-center gap-3 text-decoration-none"
                                    for="tunai">
                                    <div class="p-2 rounded-circle bg-secondary bg-opacity-10">
                                        <img src="{{ asset('img/option-bayar/tunai.svg') }}" alt="" width="24"
                                            height="24" />
                                    </div>

                                    <div>
                                        <strong class="d-block fs-6 text-dark">Tunai</strong>
                                        <small class="text-muted"> Bayar di garasi dengan kode pembayaran. Berlaku 3
                                            jam.</small>
                                    </div>
                                </label>

                                <div class="form-check z-index-2">
                                    <input class="form-check-input fs-5 cursor-pointer" type="radio"
                                        name="pembayaran" id="tunai" value="tunai">
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="alert bg-warning bg-opacity-10 border-0 rounded-4 p-3 mt-3 d-flex align-items-center gap-3 w-100"
                        role="alert">
                        <img src="{{ asset('img/option-bayar/info.svg') }}" alt="info" width="22"
                            height="22" class="flex-shrink-0" />
                        <p class="mb-0 text-warning-emphasis small fw-medium">
                            Pesanan dikonfirmasi setelah pembayaran berhasil diverifikasi.
                        </p>
                    </div>
                    <div class="container p-0">
                        <button type="submit"
                            class="btn text-white w-100 py-3 rounded-pill fw-bold d-flex align-items-center justify-content-center gap-2"
                            style="background-color: #0b1d33;"> Lanjut
                            <img src="{{ asset('img/option-bayar/lanjutkn.svg') }}" alt="lanjut" width="18"
                                height="18" />
                        </button>
                    </div>
                </main>
            </form>
        </section>
    </main>
@endsection

@push('scripts')
    <script src="{{ asset('js/customer/pilihan-pembayaran.js') }}"></script>
@endpush
