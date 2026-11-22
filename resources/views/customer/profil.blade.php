@extends('layouts.customer')

@section('title', 'Profil')

@section('content')
    @php
        $namaCustomer = $namaCustomer ?? '-';
        $emailCustomer = $emailCustomer ?? '-';
        $nomorHpCustomer = $nomorHpCustomer ?? '-';
    @endphp

    <nav class="navbar px-3 w-100" style="background-color: white">
        <div class="container-fluid px-2">
            <div class="w-100 px-3 py-1 d-flex align-items-center">
                <h1 class="fw-bold m-0 fs-3" style="color: #0b1d33">Profil Saya</h1>
            </div>
        </div>
    </nav>

    <main>
        <section class="text-center py-4">
            <div class="mb-3">
                <img src="{{ $fotoProfil ? asset('storage/'.$fotoProfil) : asset('img/user-besar.svg') }}"
                    alt="Foto profil" class="rounded-circle border p-3" width="90" height="90" />
            </div>
            <h2 id="nama-profil" class="fw-bold fs-4 text-dark mb-2">
                {{ $namaCustomer }}
            </h2>
        </section>

        <section>
            <div class="container my-4 px-3">
                <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
                    <ul class="list-group list-group-flush">
                        <li class="list-group-item py-3 px-4 border-bottom">
                            <span class="d-block text-muted small mb-1">Nama</span>
                            <span id="nama-akun" class="fw-bold text-dark">{{ $namaCustomer }}</span>
                        </li>

                        <li class="list-group-item py-3 px-4 border-bottom">
                            <span class="d-block text-muted small mb-1">Email</span>
                            <span id="email-akun" class="fw-bold text-dark">{{ $emailCustomer }}</span>
                        </li>

                        <li class="list-group-item py-3 px-4 border-bottom">
                            <span class="d-block text-muted small mb-1">Nomor HP</span>
                            <span id="nomor-hp" class="fw-bold text-dark">{{ $nomorHpCustomer }}</span>
                        </li>
                    </ul>
                </div>
            </div>
        </section>
    </main>

    <div class="container my-4 pb-5 d-grid gap-3">
        <a href="{{ route('customer.edit-profil') }}" class="btn btn-outline-dark py-3 rounded-1 fw-bold"
            style="border-color: #0b1d33;">
            Edit Profil
        </a>

        <form id="formLogout" action="{{ route('logout') }}" method="POST" class="d-grid">
            @csrf
            <button type="submit" class="btn btn-outline-danger py-3 rounded-1 fw-bold">Logout</button>
        </form>
    </div>

    <nav class="navbar fixed-bottom bg-white border-top py-2 px-3 shadow-sm">
        <div class="container-fluid d-flex justify-content-around align-items-center">
            <a href="{{ route('customer.beranda') }}" aria-label="Menuju beranda"
                class="d-flex flex-column align-items-center text-decoration-none py-1 px-3">
                <img src="{{ asset('img/profil/rumah-biru.svg') }}" alt="Beranda"
                    style="width: 22px; height: 22px; opacity: 0.6" />
                <span class="small mt-1 text-muted" style="font-size: 0.7rem">Beranda</span>
            </a>
            <a href="{{ route('customer.katalog') }}" aria-label="Menuju katalog"
                class="d-flex flex-column align-items-center text-decoration-none py-1 px-3">
                <img src="{{ asset('img/profil/logo-mobil-biru.svg') }}" alt="Katalog"
                    style="width: 22px; height: 22px; opacity: 0.6" />
                <span class="small mt-1 text-muted" style="font-size: 0.7rem">Katalog</span>
            </a>
            <a href="{{ route('customer.riwayat') }}" aria-label="Menuju riwayat"
                class="d-flex flex-column align-items-center text-decoration-none py-1 px-3">
                <img src="{{ asset('img/profil/jam.svg') }}" alt="Riwayat"
                    style="width: 22px; height: 22px; opacity: 0.6" />
                <span class="small mt-1 text-muted" style="font-size: 0.7rem">Riwayat</span>
            </a>
            <a href="{{ route('customer.profil') }}" aria-label="Menuju profil"
                class="d-flex flex-column align-items-center text-decoration-none py-1 px-3 rounded-pill"
                style="background-color: rgba(255, 107, 53, 0.12)">
                <img src="{{ asset('img/profil/user-oren.svg') }}" alt="Profil"
                    style="
                        width: 22px;
                        height: 22px;
                        filter: invert(41%) sepia(85%) saturate(1436%) hue-rotate(338deg) brightness(101%) contrast(97%);
                    " />
                <span class="small mt-1 text-muted" style="font-size: 0.7rem">Profil</span>
            </a>
        </div>
    </nav>
@endsection

@push('scripts')
    <script src="{{ asset('js/customer/profil.js') }}"></script>
@endpush
