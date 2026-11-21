@extends('layouts.admin', ['active' => 'transaksi'])

@section('title', 'Pesanan / Transaksi')

@section('content')
    @php
        $warnaStatus = [
            'Menunggu' => 'warning',
            'Berjalan' => 'secondary',
            'Selesai' => 'success',
        ];
    @endphp

    @include('components.admin-header', [
        'kicker' => 'OPERASION SEWA',
        'judul' => 'Pesanan / Transaksi',
        'deskripsi' => 'Kelola pesanan aktif dari pembayaran awal hingga dikembalikan',
    ])

    <main>
        <section class="container py-3">
            <div class="row g-3">
                <div class="col-md-3">
                    <div class="card border-0 shadow-sm rounded-4 p-4 bg-white h-100">
                        <h4 class="fw-bold">{{ $statistik['semua_pesanan'] ?? 0 }}</h4>
                        <p class="text-muted small mb-0">Semua pesanan</p>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card border-0 shadow-sm rounded-4 p-4 bg-white h-100">
                        <h4 class="fw-bold" style="color:#ff7a3d">{{ $statistik['menunggu_konfirmasi'] ?? 0 }}</h4>
                        <p class="text-muted small mb-0">Menunggu konfirmasi</p>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card border-0 shadow-sm rounded-4 p-4 bg-white h-100">
                        <h4 class="fw-bold">{{ $statistik['sedang_berjalan'] ?? 0 }}</h4>
                        <p class="text-muted small mb-0">Sedang berjalan</p>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card border-0 shadow-sm rounded-4 p-4 bg-white h-100">
                        <h4 class="fw-bold">{{ $statistik['selesai'] ?? 0 }}</h4>
                        <p class="text-muted small mb-0">Selesai</p>
                    </div>
                </div>
            </div>
        </section>

        <section>
            <nav class="navigasi-button d-flex justify-content-between">
                <div class="button-container d-flex justify-content-start">
                    <button type="button" class="button-konfirmasi btn btn-outline-secondary">Konfirmasi</button>
                    <button type="button" class="button-qris active btn btn-outline-secondary">QRIS</button>
                    <button type="button" class="button-tunai active btn btn-outline-secondary">TUNAI</button>
                    <button type="button" class="button-penyelesaian btn btn-outline-success">Penyelesaian</button>
                </div>

                <form action="{{ url('admin/transaksi') }}" method="GET" class="search-transaksi d-flex align-items-center gap-2" style="max-width: 350px;">
                    <input type="search" name="keyword" value="{{ request('keyword') }}" placeholder="Cari berdasarkan kata kunci" class="input form-control rounded-3 px-3 py-2 border-1 flex-grow-1" style="background-color: aliceblue;" />
                    <button type="submit" class="button-search btn position-absolute top-50 translate-middle-y end-0 me-1 rounded-2 d-flex align-items-center justify-content-center" style="width: 32px; height: 32px; background-color: #ff7a00; border: none;">
                        <img src="{{ asset('img_admin/transaksi/search.svg') }}" alt="Cari" />
                    </button>
                </form>
            </nav>

            <div class="col-12">
                <div class="card border-0 shadow-sm rounded-4 p-4 bg-white h-100 mb-3 my-2">
                    <div class="table-responsive rounded-top-4 overflow-hidden shadow-sm">
                        <table class="table align-middle mb-0 border-0">
                            <thead style="background-color: #0d1b2a;">
                                <tr>
                                    <th class="py-3 ps-3 border-0 text-white" style="background-color: #0d1b2a; color: white !important;">KODE</th>
                                    <th class="py-3 border-0" style="background-color: #0d1b2a; color: white !important;">CUSTOMER</th>
                                    <th class="py-3 border-0" style="background-color: #0d1b2a; color: white !important;">MOBIL</th>
                                    <th class="py-3 border-0" style="background-color: #0d1b2a; color: white !important;">TANGGAL SEWA</th>
                                    <th class="py-3 border-0" style="background-color: #0d1b2a; color: white !important;">TOTAL</th>
                                    <th class="py-3 border-0" style="background-color: #0d1b2a; color: white !important;">STATUS</th>
                                    <th class="py-3 pe-3 border-0 text-white" style="background-color: #0d1b2a; color: white !important;">AKSI</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($transaksis ?? [] as $transaksi)
                                    @php
                                        $kelasStatus = $warnaStatus[$transaksi->status] ?? 'warning';
                                    @endphp
                                    <tr>
                                        <td class="ps-3 fw-bold">{{ $transaksi->kode }}</td>
                                        <td>{{ $transaksi->nama_customer }}</td>
                                        <td>{{ $transaksi->nama_mobil }}</td>
                                        <td>{{ $transaksi->tgl_sewa }}</td>
                                        <td>{{ $transaksi->total_biaya }}</td>
                                        <td><span class="badge bg-{{ $kelasStatus }}-subtle text-{{ $kelasStatus }} border border-{{ $kelasStatus }}-subtle px-3 py-2 rounded-pill">{{ $transaksi->status }}</span></td>
                                        <td class="pe-3">
                                            <a href="{{ url('admin/transaksi/' . $transaksi->id) }}" class="btn btn-sm btn-light border px-3 rounded-pill">Detail</a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="text-center text-muted py-4">Belum ada transaksi</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </section>
    </main>
@endsection

@push('styles')
    <style>
        .search-transaksi {
            align-items: center;
            justify-content: flex-end;
            margin: 0;
            padding: 8px;
            box-sizing: border-box;
            position: relative;
            height: 35px;
            width: clamp(250px, 30%, 340px);
        }

        .input {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            padding: 0 35px 0 10px;
            box-sizing: border-box;
        }

        .button-search {
            position: absolute;
            right: 6px;
            top: 50%;
            transform: translateY(-50%);
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 4px 6px;
            z-index: 2;
        }

        .button-container {
            width: fit-content;
            padding: 0;
            gap: 0.1rem;
            transition: all 0.4s ease-in-out;
        }

        .button-container.active {
            gap: 0.5rem;
        }

        .button-qris.active,
        .button-tunai.active {
            padding: 0;
            max-width: 0;
            overflow: hidden;
            opacity: 0;
        }

        .button-qris,
        .button-tunai {
            padding: 0 0.5rem;
            max-width: 250px;
            opacity: 1;
            transition: all 0.4s ease-in-out;
        }
    </style>
@endpush

@push('scripts')
    <script src="{{ asset('js/transaksi.js') }}"></script>
@endpush
