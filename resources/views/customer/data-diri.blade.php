@extends('layouts.customer')

@section('title', 'Data Diri')

@section('body-class', '')

@section('body-style', 'color: #0b1d33')

@section('content')
    <nav class="navbar bg-white py-3 border-bottom">
        <div class="container-fluid position-relative d-flex justify-content-center align-items-center px-3">
            <a href="{{ url('/customer/syarat-dan-ketentuan') }}" aria-label="Kembali ke Syarat dan ketentuan"
                class="position-absolute start-0 ms-3 d-flex align-items-center justify-content-center rounded-circle bg-light text-decoration-none text-dark fw-bold fs-3 pb-1"
                style="width: 40px; height: 40px; color: #0b1d33;">&lsaquo;</a>
            <h2 class="fs-3 fw-bold m-0" style="color: #0b1d33;">Data Diri</h2>
        </div>
    </nav>

    <main>
        <h2 class="fs-4 fw-bold px-3 my-2">Lengkapi data penyewa</h2>
        <p class="text-secondary small px-3 my-1">Pastikan data sesuai dokumen resmi untuk proses verifikasi.</p>

        <form action="{{ url('/customer/pilihan-layanan') }}" method="POST" enctype="multipart/form-data" class="my-4">
            @csrf

            <div class="mb-4 px-3">
                <label for="nama-lengkap" class="h8 fw-bold">Nama Lengkap</label>
                <input type="text" class="form-control mt-1" style="border-color: #0b1d33" id="nama-lengkap"
                    name="nama_lengkap" placeholder="Masukkan nama lengkap" value="{{ old('nama_lengkap') }}" required />
            </div>

            <div class="mb-4 px-3">
                <label for="nomor-hp" class="h8 fw-bold">Nomor HP</label>
                <input type="tel" class="form-control mt-1" style="border-color: #0b1d33" id="nomor-hp"
                    name="nomor_hp" placeholder="08xxxxxxxxxx" value="{{ old('nomor_hp') }}" required />
            </div>

            <div class="mb-4 px-3">
                <label for="nik" class="h8 fw-bold">NIK</label>
                <input type="text" class="form-control mt-1" style="border-color: #0b1d33" id="nik"
                    name="nik" placeholder="16 digit nomor KTP" inputmode="numeric" value="{{ old('nik') }}"
                    required />
            </div>

            <div class="mb-4 px-3">
                <label for="foto-KTP" class="form-label fw-bold" style="color: #0b1d33;">Foto KTP</label>
                <label for="foto-KTP" class="w-100 bg-white rounded-4 p-4 text-center d-block cursor-pointer"
                    style="border: 2px dashed #a0aec0; cursor: pointer;">
                    <img src="{{ asset('img/data-diri/upload.svg') }}" alt="Upload Icon" class="mb-2" width="24"
                        height="24" />
                    <p class="text-secondary small mb-0 fw-medium">Unggah / ambil foto</p>
                    <input type="file" class="d-none" id="foto-KTP" name="foto_ktp" accept="image/*" required />
                </label>
            </div>

            <div class="mb-4 px-3">
                <label for="swafoto" class="form-label fw-bold" style="color: #0b1d33;">Swafoto (Selfie)</label>
                <label for="swafoto" class="w-100 bg-white rounded-4 p-4 text-center d-block cursor-pointer"
                    style="border: 2px dashed #a0aec0; cursor: pointer;">
                    <img src="{{ asset('img/data-diri/upload.svg') }}" alt="Upload Icon" class="mb-2" width="24"
                        height="24" />
                    <p class="text-secondary small mb-0 fw-medium">Unggah / ambil foto</p>
                    <input type="file" class="d-none" id="swafoto" name="swafoto" accept="image/*" required />
                </label>
            </div>

            <div class="p-3 rounded-4 d-flex align-items-center gap-3 mb-4" style="background-color: #eef7fc;">
                <img src="{{ asset('img/data-diri/icon-data-anda-terenkripsi.svg') }}" alt="Ikon Enkripsi"
                    class="flex-shrink-0" width="24" height="24" />
                <p class="text-secondary small mb-0 fw-medium"> Data Anda dienkripsi dan hanya digunakan untuk verifikasi
                    penyewaan.</p>
            </div>

            <div class="fixed-bottom bg-white py-3 px-3 border-top shadow-sm">
                <div class="container p-0">
                    <button type="submit"
                        class="btn text-white w-100 py-3 rounded-pill fw-bold d-flex align-items-center justify-content-center gap-2"
                        style="background-color: #0b1d33;"> Lanjut
                        <img src="{{ asset('img/data-diri/lanjut.svg') }}" alt="lanjut" width="18"
                            height="18" />
                    </button>
                </div>
            </div>
        </form>
    </main>
@endsection
