@extends('layouts.customer')

@section('title', 'Edit Profil')

@section('body-class', '')

@section('body-style', 'color: #0b1d33')

@section('content')
    <nav class="navbar px-4" style="background-color: white">
        <div class="rounded-4 p-2 d-flex align-items-center justify-content-center gap-4" style="background-color: white">
            <a href="{{ route('customer.profil') }}" aria-label="Kembali ke profil"
                class="text-decoration-none fw-bold fs-3 align-items-center" style="color: #0b1d33; line-height: 1">&lsaquo;</a>
            <h2 class="fw-bold m-0 fs-3" style="line-height: 1">
                <span style="color: #0b1d33">Edit Profil</span>
            </h2>
        </div>
    </nav>

    <form id="editProfilForm" action="{{ route('customer.edit-profil.update') }}" method="POST" enctype="multipart/form-data">
        @csrf

        <div class="mb-4 px-3">
            <label for="foto-profil" class="form-label fw-bold" style="color: #0b1d33;">Foto Profil</label>
            <label for="foto-profil" class="w-100 bg-white rounded-4 p-4 text-center d-block cursor-pointer"
                style="border: 2px dashed #a0aec0; cursor: pointer;">
                <img id="preview-foto-profil"
                    src="{{ $fotoProfil ? asset('storage/'.$fotoProfil) : asset('img/edit profil/upload.svg') }}"
                    alt="Pratinjau foto profil"
                    class="{{ $fotoProfil ? 'rounded-circle object-fit-cover mb-2' : 'mb-2' }}"
                    width="{{ $fotoProfil ? 80 : 24 }}" height="{{ $fotoProfil ? 80 : 24 }}" />
                <p id="teks-unggah-foto" class="text-secondary small mb-0 fw-medium">
                    {{ $fotoProfil ? 'Ketuk untuk mengganti foto' : 'Unggah / ambil foto' }}
                </p>
                <input type="file" class="d-none" id="foto-profil" name="photo_profil"
                    accept="image/jpeg,image/png,image/webp" />
            </label>

            @if ($fotoProfil)
                <div class="form-check mt-2">
                    <input class="form-check-input" type="checkbox" name="hapus_foto_profil" id="hapus-foto-profil"
                        value="1">
                    <label class="form-check-label small text-danger" for="hapus-foto-profil">
                        Hapus foto profil
                    </label>
                </div>
            @endif
        </div>

        <div class="mb-4 px-3">
            <label for="nama" class="h8 fw-bold">Nama Lengkap</label>
            <input type="text" class="form-control mt-1" style="border-color: #0b1d33" id="nama"
                name="name" placeholder="Nama lengkap" value="{{ old('name', $namaCustomer ?? '') }}" required />
        </div>

        <div class="mb-4 px-3">
            <label for="email" class="h8 fw-bold">Email</label>
            <input type="email" class="form-control mt-1" style="border-color: #0b1d33" id="email"
                name="email" placeholder="contoh@email.com" value="{{ old('email', $emailCustomer ?? '') }}" required />
        </div>

        <div class="mb-4 px-3">
            <label for="nomor-hp" class="h8 fw-bold">Nomor HP</label>
            <input type="tel" class="form-control mt-1" style="border-color: #0b1d33" id="nomor-hp"
                name="no_hp" placeholder="08xxxxxxxxxx" value="{{ old('no_hp', $nomorHpCustomer ?? '') }}" required />
        </div>

        <div class="mb-4 px-3">
            <label for="password" class="h8 fw-bold">Password Baru</label>
            <input type="password" class="form-control mt-1" style="border-color: #0b1d33" id="password"
                name="password" placeholder="Minimal 8 karakter" />
        </div>

        <div class="mb-4 px-3">
            <label for="konfirmasi-password" class="h8 fw-bold">Konfirmasi Password</label>
            <input type="password" class="form-control mt-1" style="border-color: #0b1d33"
                id="konfirmasi-password" name="password_confirmation" placeholder="Ulangi password" />
        </div>

        <div class="d-grid gap-2 px-3">
            <div id="messageBox"
                class="text-center mb-2 fw-medium fs-6 {{ $errors->any() ? 'text-danger' : 'd-none' }}">
                {{ $errors->first() }}
            </div>

            <button id="btnSimpan" class="btn text-white fw-bold py-2" type="submit"
                style="background-color: #0b1d33; border-color: #0b1d33">
                Simpan
            </button>
        </div>
    </form>
@endsection

@push('scripts')
    <script src="{{ asset('js/customer/edit-profil.js') }}"></script>
@endpush
