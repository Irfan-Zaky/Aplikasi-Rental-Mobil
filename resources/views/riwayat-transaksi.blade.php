@extends('layouts.admin', ['active' => 'riwayat'])

@section('title', 'Riwayat Transaksi')

@section('content')
    @include('components.admin-header', [
        'kicker' => 'ARSIP OPERASIONAL',
        'judul' => 'Riwayat Transaksi',
        'deskripsi' => 'Telusuri transaksi yang telah selesai atau dibatalkan untuk kebutuhan audit dan layanan pelanggan',
    ])

    <main>
        <section class="container py-3">
            <div class="row g-3">
                <div class="col-md-3">
                    <div class="card border-0 shadow-sm rounded-4 p-4 bg-white h-100">
                        <h4 class="fw-bold">Rp {{ $statistik['pendapatan_bulan_ini'] ?? '86,4 jt' }}</h4>
                        <p class="text-muted small mb-0">Pendapatan bulan ini</p>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card border-0 shadow-sm rounded-4 p-4 bg-white h-100">
                        <h4 class="fw-bold" style="color:#ff7a3d">{{ $statistik['transaksi_selesai'] ?? 42 }}</h4>
                        <p class="text-muted small mb-0">Transaksi selesai</p>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card border-0 shadow-sm rounded-4 p-4 bg-white h-100">
                        <h4 class="fw-bold">{{ $statistik['dibatalkan'] ?? 2 }}</h4>
                        <p class="text-muted small mb-0">Dibatalkan</p>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card border-0 shadow-sm rounded-4 p-4 bg-white h-100">
                        <h4 class="fw-bold">{{ $statistik['rata_rata_durasi'] ?? '4,8 hari' }}</h4>
                        <p class="text-muted small mb-0">Rata-rata durasi</p>
                    </div>
                </div>
            </div>
        </section>

        <div class="col-12">
            <div class="card border-0 shadow-sm rounded-4 p-4 bg-white">
                <form action="{{ route('admin.riwayat-transaksi') }}" method="GET" class="d-flex align-items-center mb-4 gap-3 flex-wrap">
                    <div class="flex-grow-1 position-relative" style="min-width: 250px;">
                        <input type="search" name="keyword" value="{{ request('keyword') }}" class="form-control bg-light border-1 py-2 ps-4 pe-5 rounded-pill shadow-sm" style="background-color: aliceblue;" placeholder="Cari nama, merk, plat nomor, atau kode" />
                    </div>

                    <div style="width: 150px;">
                        <select id="merk" name="merk" class="form-select bg-light border-1 py-2 px-3 rounded-pill shadow-sm" onchange="this.form.submit()">
                            <option value="">Semua Merk</option>
                            @foreach ($daftarMerk ?? [] as $itemMerk)
                                <option value="{{ $itemMerk }}" @selected(request('merk') === $itemMerk)>{{ $itemMerk }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div style="width: 140px;">
                        <select id="jenis" name="jenis" class="form-select bg-light border-1 py-2 px-3 rounded-pill shadow-sm" onchange="this.form.submit()">
                            <option value="">Semua Jenis</option>
                            @foreach ($daftarJenis ?? [] as $itemJenis)
                                <option value="{{ $itemJenis }}" @selected(request('jenis') === $itemJenis)>{{ $itemJenis }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div style="width: 150px;">
                        <select id="status" name="status" class="form-select bg-light border-1 py-2 px-3 rounded-pill shadow-sm" onchange="this.form.submit()">
                            <option value="">Semua Status</option>
                            <option value="Selesai" @selected(request('status') === 'Selesai')>Selesai</option>
                            <option value="Dibatalkan" @selected(request('status') === 'Dibatalkan')>Dibatalkan</option>
                        </select>
                    </div>

                    @if(request('keyword') || request('merk') || request('jenis') || request('status'))
                        <div>
                            <a href="{{ route('admin.riwayat-transaksi') }}" class="btn btn-outline-secondary rounded-pill py-2 px-3 small">Reset Filter</a>
                        </div>
                    @endif
                </form>

                <div class="table-responsive rounded-top-4 overflow-hidden shadow-sm">
                    <table class="table align-middle mb-0 border-0">
                        <thead style="background-color: #0d1b2a;">
                            <tr>
                                <th class="py-3 ps-3 border-0 text-white" style="background-color: #0d1b2a; color: white !important;">KODE</th>
                                <th class="py-3 border-0" style="background-color: #0d1b2a; color: white !important;">CUSTOMER</th>
                                <th class="py-3 border-0" style="background-color: #0d1b2a; color: white !important;">MOBIL</th>
                                <th class="py-3 border-0" style="background-color: #0d1b2a; color: white !important;">TANGGAL SEWA</th>
                                <th class="py-3 border-0" style="background-color: #0d1b2a; color: white !important;">BAYAR</th>
                                <th class="py-3 border-0" style="background-color: #0d1b2a; color: white !important;">TOTAL</th>
                                <th class="py-3 border-0" style="background-color: #0d1b2a; color: white !important;">STATUS</th>
                                <th class="py-3 pe-3 border-0 text-white" style="background-color: #0d1b2a; color: white !important;">AKSI</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($riwayatTransaksis ?? [] as $transaksi)
                                @php
                                    $kelasStatus = $transaksi->status === 'Dibatalkan' ? 'danger' : 'success';
                                @endphp
                                <tr>
                                    <td class="ps-3 fw-bold">{{ $transaksi->kode }}</td>
                                    <td>{{ $transaksi->nama_customer }}</td>
                                    <td>{{ $transaksi->nama_mobil }}</td>
                                    <td>{{ $transaksi->tgl_sewa }}</td>
                                    <td>{{ $transaksi->metode_pembayaran ?? 'Tunai / QRIS' }}</td>
                                    <td>{{ $transaksi->total_biaya }}</td>
                                    <td><span class="badge bg-{{ $kelasStatus }}-subtle text-{{ $kelasStatus }} border border-{{ $kelasStatus }}-subtle px-3 py-2 rounded-pill">{{ $transaksi->status }}</span></td>
                                    <td class="pe-3">
                                        <a href="{{ url('admin/riwayat-transaksi/' . $transaksi->id) }}" class="btn btn-sm btn-light border px-3 rounded-pill">Detail</a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="text-center text-muted py-4">Belum ada riwayat transaksi</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </main>
@endsection
