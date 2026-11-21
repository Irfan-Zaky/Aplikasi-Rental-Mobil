@php
    $active = $active ?? '';
@endphp

<nav class="d-flex flex-column justify-content-between p-4 text-white flex-shrink-0" style="background-color: #0b1d33; width: 280px;">
    <div>
        <div class="d-flex align-items-center gap-3 mb-5">
            <div class="rounded-circle d-flex justify-content-center align-items-center p-2" style="background: aliceblue; width: 50px; height: 50px;">
                <img src="{{ asset('img_admin/data-mobil/gambar.svg') }}" alt="Foto Admin" width="28" height="28" />
            </div>
            <div>
                <h6 class="m-0 text-white-50 small" style="color: white">Halo,</h6>
                <h5 class="m-0 fw-bold fs-6" style="color: white">{{ Auth::user()->name ?? 'Nama Admin' }}</h5>
            </div>
        </div>

        <ul class="nav flex-column gap-2">
            <li class="nav-item">
                <a href="{{ route('admin.dashboard') }}" class="nav-link {{ $active === 'dashboard' ? 'text-white d-flex align-items-center gap-2 py-2 px-2' : 'text-white-50 d-flex align-items-center gap-3 py-2 px-3' }} rounded-pill" @style(['background-color: #ff7a3d;' => $active === 'dashboard']) title="Beranda">
                    <img src="{{ asset('img_admin/data-mobil/home.svg') }}" alt="" width="20" height="20" />
                    <span class="{{ $active === 'dashboard' ? 'fw-medium' : '' }}" style="color: {{ $active === 'dashboard' ? 'white' : '#ff7a3d' }}">Beranda</span>
                </a>
            </li>

            <li class="nav-item">
                <a href="{{ route('admin.data-mobil.index') }}" class="nav-link {{ $active === 'data-mobil' ? 'text-white d-flex align-items-center gap-2 py-2 px-2' : 'text-white-50 d-flex align-items-center gap-3 py-2 px-3' }} rounded-pill" @style(['background-color: #ff7a3d;' => $active === 'data-mobil']) title="Data Mobil">
                    <img src="{{ asset('img_admin/dashboard/icon-data-mobil.svg') }}" alt="" width="20" height="20" />
                    <span class="{{ $active === 'data-mobil' ? 'fw-medium' : '' }}" style="color: {{ $active === 'data-mobil' ? 'white' : '#ff7a3d' }}">Data Mobil</span>
                </a>
            </li>

            <li class="nav-item">
                <a href="{{ route('admin.transaksi') }}" class="nav-link {{ $active === 'transaksi' ? 'text-white d-flex align-items-center gap-2 py-2 px-2' : 'text-black-50 d-flex align-items-center gap-3 py-2 px-3' }} rounded-pill" @style(['background-color: #ff7a3d;' => $active === 'transaksi']) title="Transaksi">
                    <img src="{{ asset('img_admin/data-mobil/icon-pesanan.svg') }}" alt="" width="20" height="20" />
                    <span class="{{ $active === 'transaksi' ? 'fw-medium' : '' }}" style="color: {{ $active === 'transaksi' ? 'white' : '#ff7a3d' }}">Pesanan</span>
                </a>
            </li>

            <li class="nav-item">
                <a href="{{ route('admin.riwayat-transaksi') }}" class="nav-link {{ $active === 'riwayat' ? 'text-white d-flex align-items-center gap-2 py-2 px-2' : 'text-white-50 d-flex align-items-center gap-3 py-2 px-3' }} rounded-pill" @style(['background-color: #ff7a3d;' => $active === 'riwayat']) title="Riwayat">
                    <img src="{{ asset('img_admin/data-mobil/icon-riwayat.svg') }}" alt="" width="20" height="20" />
                    <span class="{{ $active === 'riwayat' ? 'fw-medium' : '' }}" style="color: {{ $active === 'riwayat' ? 'white' : '#ff7a3d' }}">Riwayat</span>
                </a>
            </li>
        </ul>
    </div>

    <div>
        <form action="{{ route('logout') }}" method="POST" class="form-konfirmasi" data-pesan="Yakin ingin keluar dari akun ini?">
            @csrf
            <button type="submit" class="btn text-white-50 d-flex align-items-center gap-3 p-0 border-0 bg-transparent">
                <img src="{{ asset('img_admin/data-mobil/log-out.svg') }}" alt="" width="20" height="20" />
                <span style="color: white">Log Out</span>
            </button>
        </form>
    </div>
</nav>
