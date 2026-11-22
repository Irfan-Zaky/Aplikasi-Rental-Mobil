@extends('layouts.customer')

@section('title', 'Login')

@section('body-class', '')

@section('body-style', 'color: #0b1d33')

@section('content')
    <nav class="navbar px-4" style="background-color: white">
        <div class="rounded-4 p-2 d-flex align-items-center justify-content-center gap-4" style="background-color: white">
            <a href="{{ url('/customer/landing-page') }}" aria-label="Kembali ke halaman awal"
                class="text-decoration-none fw-bold fs-3 align-items-center" style="color: #0b1d33; line-height: 1">&lsaquo;</a>
            <h2 class="fw-bold m-0 fs-3" style="line-height: 1">
                <span style="color: #0b1d33">Login</span>
            </h2>
        </div>
    </nav>

    <main style="color: #0b1d33">
        <div class="d-flex flex-column align-items-center gap-2">
            <div class="text-center mb-4">
                <img src="{{ asset('img/login page/logo login.svg') }}" alt="Logo Gorental" width="75"
                    class="img-fluid" />
            </div>

            <header class="text-center">
                <h1 class="display-6 display-md-3 fw-bold mb-3">
                    Masuk ke Akun Anda
                </h1>
                <p class="text-secondary fs-6 mb-4" style="line-height: 1.6">
                    Selamat datang kembali! Silakan masuk untuk melanjutkan.
                </p>
            </header>

            <section style="max-width: 500px; width: 100%; margin: 0 auto">
                <form id="loginForm" action="{{ url('/customer/login') }}" method="POST">
                    @csrf

                    <div class="mb-4 px-3">
                        <label for="login" class="form-label">Email / Nomor HP / Username</label>
                        <input type="text" class="form-control" style="border-color: #0b1d33" id="login"
                            name="login" placeholder="email, nomor HP, atau username" value="{{ old('login') }}" required />
                    </div>

                    <div class="mb-4 px-3">
                        <label for="password" class="form-label">Password</label>
                        <input type="password" class="form-control" style="border-color: #0b1d33" id="password"
                            name="password" placeholder="********" minlength="8" required />
                    </div>

                    <div class="d-grid gap-2 px-3">
                        <div id="errorMessage"
                            class="text-danger text-center mb-2 fw-medium fs-6 {{ $errors->any() ? '' : 'd-none' }}">
                            {{ $errors->first() }}
                        </div>

                        <button id="loginBtn" class="btn text-white fw-bold py-2" type="submit"
                            style="background-color: #0b1d33; border-color: #0b1d33">
                            Login
                        </button>
                    </div>
                </form>
            </section>

            <section class="mt-3">
                <p>Belum punya akun? <a href="{{ url('/customer/daftar-akun') }}">Daftar</a></p>
            </section>
        </div>
    </main>
@endsection

@push('scripts')
    <script src="{{ asset('js/customer/login.js') }}"></script>
@endpush
