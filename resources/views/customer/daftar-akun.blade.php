@extends('layouts.customer')

@section('title', 'Daftar Akun')

@section('body-class', '')

@section('body-style', 'color: #0b1d33')

@section('content')
    <nav class="navbar px-4" style="background-color: white">
        <div class="rounded-4 p-2 d-flex align-items-center justify-content-center gap-4" style="background-color: white">
            <a href="{{ url('/customer/landing-page') }}" aria-label="Kembali ke halaman awal"
                class="text-decoration-none fw-bold fs-2 align-items-center" style="color: #0b1d33; line-height: 1">&lsaquo;</a>
            <h2 class="fw-bold m-0 fs-4" style="line-height: 1">
                <span style="color: #0b1d33">Daftar Akun</span>
            </h2>
        </div>
    </nav>

    <main style="color: #0b1d33">
        <div class="d-flex flex-column align-items-center gap-2">
            <header>
                <h1 class="display-6 display-md-3 fw-bold mb-3">Daftar Akun</h1>
            </header>

            <section style="max-width: 500px; width: 100%; margin: 0 auto">
                <form id="registerForm" action="{{ url('/customer/daftar-akun') }}" method="POST">
                    @csrf

                    <div class="mb-4 px-3">
                        <label for="nama" class="form-label">Nama Lengkap:</label>
                        <input type="text" class="form-control" style="border-color: #0b1d33" id="nama"
                            name="name" placeholder="Nama sesui KTP" value="{{ old('name') }}" required />
                    </div>

                    <div class="mb-4 px-3">
                        <label for="email" class="form-label">Email:</label>
                        <input type="email" class="form-control" style="border-color: #0b1d33" id="email"
                            name="email" placeholder="contoh@gmail.com" value="{{ old('email') }}" required />
                    </div>

                    <div class="mb-4 px-3">
                        <label for="no" class="form-label">Nomor Handphone:</label>
                        <input type="tel" class="form-control" style="border-color: #0b1d33" id="no"
                            name="no_hp" placeholder="08xxxxxxxxxx" value="{{ old('no_hp') }}" required />
                    </div>

                    <div class="mb-4 px-3">
                        <label for="password" class="form-label">Password</label>
                        <input type="password" class="form-control" style="border-color: #0b1d33" id="password"
                            name="password" placeholder="Minimal 8 karakter" minlength="8" required />
                    </div>

                    <div class="mb-4 px-3">
                        <label for="konfirmasi-password" class="form-label">Konfirmasi Password</label>
                        <input type="password" class="form-control" style="border-color: #0b1d33"
                            id="konfirmasi-password" name="password_confirmation" placeholder="Ulangi password"
                            minlength="8" required />
                    </div>

                    <div class="d-grid gap-2 px-3">
                        <div id="messageBox"
                            class="text-center mb-2 fw-medium fs-6 {{ $errors->any() ? 'text-danger' : 'd-none' }}">
                            {{ $errors->first() }}
                        </div>

                        <button id="registerBtn" class="btn text-white fw-bold py-2" type="submit"
                            style="background-color: #0b1d33; border-color: #0b1d33">
                            Daftar
                        </button>
                    </div>
                </form>
            </section>

            <section class="mt-3">
                <p>Sudah punya akun? <a href="{{ url('/customer/login') }}">Login</a></p>
            </section>
        </div>
    </main>
@endsection

@push('scripts')
    <script src="{{ asset('js/customer/daftar-akun.js') }}"></script>
@endpush
