@extends('layouts.admin', ['active' => 'riwayat'])

@section('title', 'Detail Riwayat Transaksi')

@section('content')
    @php
        $status = $transaksi->status ?? 'Selesai';

        $kelasStatus = match ($status) {
            'Selesai' => 'bg-light text-success border border-success',
            'Dibatalkan' => 'bg-light text-danger border border-danger',
            default => 'bg-light text-secondary border',
        };

        $kelasPembayaran = $status === 'Menunggu'
            ? 'bg-light text-warning border border-warning'
            : 'bg-light text-success border border-success-subtle';
    @endphp

    @include('components.admin-header', [
        'kicker' => 'ARSIP SELESAI',
        'judul' => 'Detail Riwayat Transaksi',
        'deskripsi' => 'Rekam lengkap transaksi, pembayaran, dan perubahan status untuk referensi operasional',
    ])

    <main>
        <div class="container py-4">
            <div class="row g-4 align-items-start">
                <div class="col-md-6">
                    <div class="card border-0 shadow-sm rounded-4 p-4 bg-white h-100">
                        <h4 class="fw-bold mb-4">Data Transaksi</h4>
                        <ul class="list-unstyled mb-4">
                            <li class="d-flex justify-content-between align-items-center py-2">
                                <span class="text-muted">Nama customer</span>
                                <strong>{{ $transaksi->nama_customer ?? '-' }}</strong>
                            </li>
                            <li class="d-flex justify-content-between align-items-center py-2">
                                <span class="text-muted">Mobil</span>
                                <strong>{{ $transaksi->nama_mobil ?? '-' }}</strong>
                            </li>
                            <li class="d-flex justify-content-between align-items-center py-2">
                                <span class="text-muted">Tanggal</span>
                                <strong>{{ $transaksi->tgl_sewa ?? '-' }}</strong>
                            </li>
                            <li class="d-flex justify-content-between align-items-center py-2">
                                <span class="text-muted">Durasi</span>
                                <strong>{{ $transaksi->durasi ?? '4 Hari' }}</strong>
                            </li>
                            <li class="d-flex justify-content-between align-items-center py-2">
                                <span class="text-muted">Layanan</span>
                                <strong>{{ $transaksi->layanan ?? 'Lepas Kunci' }}</strong>
                            </li>
                            <li class="d-flex justify-content-between align-items-center py-2">
                                <span class="text-muted">Penerimaan</span>
                                <strong>{{ $transaksi->penerimaan ?? 'Diantar' }}</strong>
                            </li>
                            <li class="d-flex justify-content-between align-items-center py-2">
                                <span class="text-muted">Status pesanan</span>
                                <span class="badge px-3 py-2 rounded-pill {{ $kelasStatus }}">{{ $status }}</span>
                            </li>
                        </ul>
                        <div class="d-flex justify-content-between align-items-center pt-2">
                            <strong class="fw-bold">Kode Booking</strong>
                            <span class="badge border bg-light px-3 py-2 rounded-pill" style="border-color: #ff7a00 !important; color: #ff7a00 !important;">{{ $transaksi->kode ?? '-' }}</span>
                        </div>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="card border-0 shadow-sm rounded-4 p-4 bg-white h-100">
                        <h4 class="fw-bold mb-4">Pembayaran & Status</h4>
                        <ul class="list-unstyled mb-0">
                            <li class="d-flex justify-content-between align-items-center py-2">
                                <span class="text-muted">Metode</span>
                                <strong>{{ $transaksi->metode_pembayaran ?? 'QRIS' }}</strong>
                            </li>
                            <li class="d-flex justify-content-between align-items-center py-2">
                                <span class="text-muted">Harga/hari</span>
                                <strong>{{ $transaksi->harga_per_hari ?? 'Rp 0' }}</strong>
                            </li>
                            <li class="d-flex justify-content-between align-items-center py-2">
                                <span class="text-muted">Subtotal sewa</span>
                                <strong>{{ $transaksi->subtotal ?? 'Rp 0' }}</strong>
                            </li>
                            <li class="d-flex justify-content-between align-items-center py-2">
                                <span class="text-muted">Biaya tambahan</span>
                                <strong>{{ $transaksi->biaya_tambahan ?? 'Rp 0' }}</strong>
                            </li>
                            <li class="d-flex justify-content-between align-items-center py-2">
                                <span class="text-muted">Denda keterlambatan</span>
                                <strong>{{ $transaksi->denda ?? 'Rp 0' }}</strong>
                            </li>
                            <li class="d-flex justify-content-between align-items-center py-2">
                                <span class="text-muted">Total pembayaran</span>
                                <strong>{{ $transaksi->total_biaya ?? 'Rp 0' }}</strong>
                            </li>
                            <li class="d-flex justify-content-between align-items-center pt-3">
                                <span class="text-muted">Status pembayaran</span>
                                <span class="badge px-3 py-2 rounded-pill {{ $kelasPembayaran }}">{{ $transaksi->status_pembayaran ?? ($status === 'Menunggu' ? 'Menunggu' : 'Berhasil') }}</span>
                            </li>
                        </ul>
                    </div>
                </div>

                <div class="card border-0 shadow-sm rounded-4 p-4 bg-white">
                    <h4 class="fw-bold mb-4">Riwayat Status</h4>
                    <div class="row g-3">
                        <div class="col-md-3">
                            <div class="card border border-secondary-subtle shadow-sm rounded-3 p-3 h-100 bg-white">
                                <div class="d-flex align-items-center gap-2 mb-2">
                                    <img src="{{ asset('img_admin/detail-riwayat-transaksi/check.svg') }}" alt="" width="20" />
                                    <h6 class="fw-bold mb-0" style="font-size: 14px;">Menunggu Pembayaran</h6>
                                </div>
                                <div>
                                    <p class="text-primary small mb-1 fw-semibold" style="font-size: 12px;">05 Okt 2026 · 09.10 WIB</p>
                                    <small class="text-muted" style="font-size: 11px;">Pesanan dibuat melalui aplikasi</small>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-3">
                            <div class="card border border-secondary-subtle shadow-sm rounded-3 p-3 h-100 bg-white">
                                <div class="d-flex align-items-center gap-2 mb-2">
                                    <img src="{{ asset('img_admin/detail-riwayat-transaksi/check.svg') }}" alt="" width="20" />
                                    <h6 class="fw-bold mb-0" style="font-size: 14px;">Pembayaran Berhasil</h6>
                                </div>
                                <div>
                                    <p class="text-primary small mb-1 fw-semibold" style="font-size: 12px;">05 Okt 2026 · 09.14 WIB</p>
                                    <small class="text-muted" style="font-size: 11px;">QRIS diverifikasi otomatis</small>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-3">
                            <div class="card border border-secondary-subtle shadow-sm rounded-3 p-3 h-100 bg-white">
                                <div class="d-flex align-items-center gap-2 mb-2">
                                    <img src="{{ asset('img_admin/detail-riwayat-transaksi/check.svg') }}" alt="" width="20" />
                                    <h6 class="fw-bold mb-0" style="font-size: 14px;">Kendaraan Diserahkan</h6>
                                </div>
                                <div>
                                    <p class="text-primary small mb-1 fw-semibold" style="font-size: 12px;">05 Okt 2026 · 08.02 WIB</p>
                                    <small class="text-muted" style="font-size: 11px;">Serah terima oleh petugas</small>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-3">
                            <div class="card shadow-sm rounded-3 p-3 h-100" style="background-color: #f0fdf4; border: 1px solid #22c55e;">
                                <div class="d-flex align-items-center gap-2 mb-2">
                                    <img src="{{ asset('img_admin/detail-riwayat-transaksi/check.svg') }}" alt="" width="20" />
                                    <h6 class="fw-bold mb-0 text-success" style="font-size: 14px;">Selesai</h6>
                                </div>
                                <div>
                                    <p class="text-success small mb-1 fw-semibold" style="font-size: 12px;">08 Okt 2026 · 16.30 WIB</p>
                                    <small class="text-muted" style="font-size: 11px;">Kendaraan diterima tanpa kerusakan</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="d-flex align-items-center gap-3 flex-wrap">
                    <a href="{{ url('admin/riwayat-transaksi') }}" class="btn btn-outline-warning px-4 py-2 rounded-3 fw-medium shadow-sm bg-light-subtle text-decoration-none" style="border-color: #ff7a00; color:#ff7a00">Kembali</a>
                </div>
            </div>
        </div>
    </main>
@endsection
