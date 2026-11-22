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

    <main class="mb-5 pb-5">
        <div class="px-3 mt-2">
            <span class="badge rounded-pill border border-primary-subtle text-primary px-3 py-2 bg-primary-subtle">
                Dengan Sopir </span>
        </div>

        <form id="formLayanan" action="{{ url('/customer/ringkasan-pemesanan') }}" method="POST">
            @csrf

            <div class="mt-4 px-3">
                <label for="alamat-penjemputan" class="h5 fw-bold">Alamat / Titik Penjemputan</label>
                <textarea class="form-control mt-1" style="border-color: #0b1d33;" id="alamat-penjemputan"
                    name="alamat_penjemputan" placeholder="Tulis alamat lengkap..." required>{{ old('alamat_penjemputan') }}</textarea>
            </div>

            <div class="p-3 px-3 mt-3 rounded-4 d-flex align-items-center gap-3 mb-4" style="background-color: #eef7fc;">
                <img src="{{ asset('img/detail-layanan-dengan-sopir/icon-sopir-sudah-diverifikasi.svg') }}"
                    alt="Ikon Enkripsi" class="flex-shrink-0" width="24" height="24" />
                <p class="text-secondary small mb-0 fw-medium"> Sopir terverifikasi akan menghubungi Anda 30 menit sebelum
                    penjemputan.</p>
            </div>

            <div class="fixed-bottom bg-white py-3 px-3 border-top shadow-sm">
                <div class="container p-0">
                    <button type="submit"
                        class="btn text-white w-100 py-3 rounded-pill fw-bold d-flex align-items-center justify-content-center gap-2"
                        style="background-color: #0b1d33;"> Lanjut
                        <img src="{{ asset('img/detail-layanan-dengan-sopir/lanjut.svg') }}" alt="lanjut"
                            width="18" height="18" />
                    </button>
                </div>
            </div>
        </form>
    </main>
@endsection
