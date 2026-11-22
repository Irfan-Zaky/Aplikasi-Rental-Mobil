@extends('layouts.customer')

@section('title', 'Detail Layanan')

@section('content')
    <nav class="navbar bg-white py-3 border-bottom">
        <div class="container-fluid position-relative d-flex justify-content-center align-items-center px-3">
            <a href="{{ url('/customer/pilihan-layanan') }}" aria-label="Kembali ke Pilihan Layanan"
                class="position-absolute start-0 ms-3 d-flex align-items-center justify-content-center rounded-circle bg-light text-decoration-none text-dark fw-bold fs-3 pb-1"
                style="width: 40px; height: 40px; color: #0b1d33;">&lsaquo;</a>
            <h2 class="fs-3 fw-bold m-0" style="color: #0b1d33;">Detail Layanan</h2>
        </div>
    </nav>

    <main>
        <form id="formLayanan" action="{{ route('customer.detail-layanan.store') }}" method="POST">
            @csrf
            <input type="hidden" name="layanan" value="lepas-kunci">

            <div class="px-3 mt-2">
                <span class="badge rounded-pill border border-primary-subtle text-primary px-3 py-2 bg-primary-subtle">
                    Lepas Kunci </span>
            </div>

            <h2 class="h5 fw-bold mt-3 px-3">Metode penerimaan mobil</h2>

            <div class="d-flex flex-column gap-3 px-3">
                <div class="card p-3 rounded-4 shadow-sm border position-relative">
                    <div class="d-flex align-items-center justify-content-between">
                        <label class="stretched-link d-flex align-items-center gap-3 text-decoration-none" for="diantar">
                            <div class="p-2 rounded-circle bg-warning bg-opacity-10">
                                <img src="{{ asset('img/lepas-kunci/diantar.svg') }}" alt="" width="24"
                                    height="24" />
                            </div>
                            <div>
                                <strong class="d-block fs-6 text-dark">Diantar ke lokasi</strong>
                                <small class="text-muted">Mobil diantar ke alamat Anda.</small>
                            </div>
                        </label>

                        <div class="form-check z-index-2">
                            <input class="form-check-input fs-5 cursor-pointer" type="radio"
                                name="metode_penerimaan" id="diantar" value="diantar" checked required>
                        </div>
                    </div>
                </div>

                <div class="card p-3 rounded-4 shadow-sm border position-relative">
                    <div class="d-flex align-items-center justify-content-between">
                        <label class="stretched-link d-flex align-items-center gap-3 text-decoration-none" for="ambil">
                            <div class="p-2 rounded-circle bg-secondary bg-opacity-10">
                                <img src="{{ asset('img/lepas-kunci/ambil-kegarasi.svg') }}" alt="" width="24"
                                    height="24" />
                            </div>

                            <div>
                                <strong class="d-block fs-6 text-dark">Ambil di garasi</strong>
                                <small class="text-muted">Ambil langsung di garasi rental.</small>
                            </div>
                        </label>

                        <div class="form-check z-index-2">
                            <input class="form-check-input fs-5 cursor-pointer" type="radio"
                                name="metode_penerimaan" id="ambil" value="ambil">
                        </div>
                    </div>
                </div>
            </div>

            <div class="px-3 mt-4" id="wadah-lokasi-pengantaran">
                <label for="lokasi-pengantaran" class="h6 fw-bold">Lokasi pengantaran </label>
                <textarea class="form-control mt-1" style="border-color: #0b1d33;" id="lokasi-pengantaran"
                    name="lokasi_pengantaran" placeholder="Tulis alamat lengkap..." rows="4">{{ old('lokasi_pengantaran') }}</textarea>
            </div>

            <div class="fixed-bottom bg-white py-3 px-3 border-top shadow-sm">
                <div class="container p-0">
                    <button type="submit"
                        class="btn text-white w-100 py-3 rounded-pill fw-bold d-flex align-items-center justify-content-center gap-2"
                        style="background-color: #0b1d33;"> Lanjut
                        <img src="{{ asset('img/lepas-kunci/lanjut.svg') }}" alt="lanjut" width="18"
                            height="18" />
                    </button>
                </div>
            </div>
        </form>
    </main>
@endsection

@push('scripts')
    <script src="{{ asset('js/customer/detail-layanan-lepas-kunci.js') }}"></script>
@endpush
