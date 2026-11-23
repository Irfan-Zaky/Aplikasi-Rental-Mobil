@extends('layouts.customer')

@section('title', 'Pilih Layanan')

@section('content')
    <nav class="navbar bg-white py-3 border-bottom">
        <div class="container-fluid position-relative d-flex justify-content-center align-items-center px-3">
            <a href="{{ route('customer.syarat') }}" aria-label="Kembali ke Syarat dan Ketentuan"
                class="position-absolute start-0 ms-3 d-flex align-items-center justify-content-center rounded-circle bg-light text-decoration-none text-dark fw-bold fs-3 pb-1"
                style="width: 40px; height: 40px; color: #0b1d33;">&lsaquo;</a>
            <h2 class="fs-3 fw-bold m-0" style="color: #0b1d33;">Pilihan Layanan</h2>
        </div>
    </nav>

    <main class="mb-5 pb-5">
        <form id="formLayanan" action="{{ route('customer.pilihan-layanan.store') }}" method="POST">
            @csrf
            <h2 class="h5 fw-bold px-3 mt-2 mb-1">Bagaimana Anda ingin bepergian?</h2>
            <p class="text-muted px-3">Pilih layanan rental yang paling sesuai dengan kebutuhan perjalanan.</p>

            <div class="d-flex flex-column gap-3 px-3">
                <div class="card p-3 rounded-4 shadow-sm border position-relative">
                    <div class="d-flex align-items-center justify-content-between">
                        <label class="stretched-link d-flex align-items-center gap-3 text-decoration-none"
                            for="dengan-sopir">
                            <div class="p-2 rounded-circle bg-warning bg-opacity-10">
                                <img src="{{ asset('img/pilih-layanan/dengan-sopir.svg') }}" alt="" width="24"
                                    height="24" />
                            </div>
                            <div>
                                <strong class="d-block fs-6 text-dark">Dengan Sopir</strong>
                                <small class="text-muted">Car dikemudikan sopir profesional dari pihak rental.</small>
                            </div>
                        </label>

                        <div class="form-check z-index-2">
                            <input class="form-check-input fs-5 cursor-pointer" type="radio" name="layanan"
                                id="dengan-sopir" value="dengan-sopir" checked required>
                        </div>
                    </div>
                </div>

                <div class="card p-3 rounded-4 shadow-sm border position-relative">
                    <div class="d-flex align-items-center justify-content-between">
                        <label class="stretched-link d-flex align-items-center gap-3 text-decoration-none"
                            for="lepas-kunci">
                            <div class="p-2 rounded-circle bg-secondary bg-opacity-10">
                                <img src="{{ asset('img/pilih-layanan/kunci.svg') }}" alt="" width="24"
                                    height="24" />
                            </div>

                            <div>
                                <strong class="d-block fs-6 text-dark">Lepas Kunci</strong>
                                <small class="text-muted"> Anda mengemudikan mobil sendiri dengan proses serah terima
                                    mudah.</small>
                            </div>
                        </label>

                        <div class="form-check z-index-2">
                            <input class="form-check-input fs-5 cursor-pointer" type="radio" name="layanan"
                                id="lepas-kunci" value="lepas-kunci">
                        </div>
                    </div>
                </div>
            </div>

            <div
                class="px-3 mt-2 card border-0 shadow-sm rounded-4 bg-rounded p-3 d-flex flex-row align-items-start gap-3">
                <img src="{{ asset('img/pilih-layanan/rekomendasi-perjalanan.svg') }}" alt="" width="28"
                    height="28" class="mt-1" />
                <div>
                    <h3 class="fs-6 fw-bold text-dark mb-1">Rekomendasi perjalanan santai</h3>
                    <p class="text-muted small mb-0">Pilih dengan sopir untuk perjalanan tanpa repot parkir dan rute.</p>
                </div>
            </div>

            <div class="fixed-bottom bg-white py-3 px-3 border-top shadow-sm">
                <div class="container p-0">
                    <button type="submit"
                        class="btn text-white w-100 py-3 rounded-pill fw-bold d-flex align-items-center justify-content-center gap-2"
                        style="background-color: #0b1d33;"> Lanjut
                        <img src="{{ asset('img/pilih-layanan/lanjut.svg') }}" alt="lanjut" width="18"
                            height="18" />
                    </button>
                </div>
            </div>
        </form>
    </main>
@endsection

@push('scripts')
    <script src="{{ asset('js/customer/pilihan-layanan.js') }}"></script>
@endpush
