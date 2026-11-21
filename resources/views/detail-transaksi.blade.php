@extends('layouts.admin', ['active' => 'transaksi'])

@section('title', 'Detail Pesanan / Transaksi')

@section('content')
    @include('components.admin-header', [
        'kicker' => 'TINDAKAN TRANSAKSI',
        'judul' => 'Detail Pesanan / Transaksi',
        'deskripsi' => 'Tinjau identitas, jadwal sewa, pembayaran, dan dokumen verifikasi sebelum memproses pesanan',
    ])

    <main>
        <section>
            <div class="container py-4">
                <div class="row g-4 align-items-start">
                    <div class="col-12 col-lg-6">
                        <div class="card border-0 shadow-sm rounded-4 p-4 bg-white h-100">
                            <div class="card-data-customer">
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <h5 class="fw-bold text-dark m-0">Data Customer & Pesanan</h5>
                                    <span class="gap-1">
                                        <strong class="text-white rounded-circle d-inline-flex justify-content-center align-items-center" style="width: 28px; height: 28px; background-color: #ff7a3d; font-size: 11px;">{{ $transaksi->inisial ?? 'NP' }}</strong> Pelanggan terverifikasi
                                    </span>
                                </div>

                                <ul class="list-unstyled mb-4">
                                    <li class="d-flex justify-content-between align-items-center py-2 border-bottom border-light">
                                        <span class="text-muted small">Nama customer</span>
                                        <strong class="text-dark small">{{ $transaksi->nama_customer ?? 'Nadia Putri' }}</strong>
                                    </li>
                                    <li class="d-flex justify-content-between align-items-center py-2 border-bottom border-light">
                                        <span class="text-muted small">Nomor HP</span>
                                        <strong class="text-dark small">{{ $transaksi->no_hp ?? '0812 8840 2196' }}</strong>
                                    </li>
                                    <li class="d-flex justify-content-between align-items-center py-2 border-bottom border-light">
                                        <span class="text-muted small">Mobil</span>
                                        <strong class="text-dark small">{{ $transaksi->nama_mobil ?? 'Toyota Avanza 1.5 G' }}</strong>
                                    </li>
                                    <li class="d-flex justify-content-between align-items-center py-2 border-bottom border-light">
                                        <span class="text-muted small">Tanggal sewa</span>
                                        <strong class="text-dark small">{{ $transaksi->tgl_sewa ?? '12-14 Oktober 2026' }}</strong>
                                    </li>
                                    <li class="d-flex justify-content-between align-items-center py-2 border-bottom border-light">
                                        <span class="text-muted small">Durasi</span>
                                        <strong class="text-dark small">{{ $transaksi->durasi ?? '4 hari' }}</strong>
                                    </li>
                                    <li class="d-flex justify-content-between align-items-center py-2 border-bottom border-light">
                                        <span class="text-muted small">Layanan</span>
                                        <strong class="text-dark small">{{ $transaksi->layanan ?? 'Lepas Kunci' }}</strong>
                                    </li>
                                    <li class="d-flex justify-content-between align-items-center py-2 border-bottom border-light">
                                        <span class="text-muted small">Penerimaan</span>
                                        <strong class="text-dark small">{{ $transaksi->penerimaan ?? 'Diantar · 08.00 WIB' }}</strong>
                                    </li>
                                    <li class="d-flex justify-content-between align-items-center py-2">
                                        <span class="text-muted small">Status pesanan</span>
                                        <span class="badge bg-warning-subtle text-warning border border-warning-subtle px-3 py-1 rounded-pill small fw-medium">{{ $transaksi->status ?? 'Menunggu Verifikasi' }}</span>
                                    </li>
                                </ul>

                                <div class="bg-light p-3 rounded-4 d-flex justify-content-between align-items-center mt-auto border">
                                    <span class="text-muted small fw-medium">Kode Booking</span>
                                    <span class="badge bg-warning-subtle text-warning border border-warning-subtle px-3 py-1 rounded-pill fw-bold">{{ $transaksi->kode_booking ?? 'BK-104829' }}</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-12 col-lg-6">
                        <div class="card border-0 shadow-sm rounded-4 p-4 bg-white h-100">
                            <h5 class="fw-bold text-dark mb-3">Pembayaran</h5>

                            <ul class="list-unstyled mb-0">
                                <li class="d-flex justify-content-between align-items-center py-2 border-bottom border-light">
                                    <span class="text-muted small">Metode</span>
                                    <strong class="text-dark small">{{ $transaksi->metode_pembayaran ?? 'Tunai' }}</strong>
                                </li>
                                <li class="d-flex justify-content-between align-items-center py-2 border-bottom border-light">
                                    <span class="text-muted small">Kode pembayaran</span>
                                    <strong class="text-dark small">{{ $transaksi->kode_pembayaran ?? 'TNI-2841-9820' }}</strong>
                                </li>
                                <li class="d-flex justify-content-between align-items-center py-2 border-bottom border-light">
                                    <span class="text-muted small">Harga/hari</span>
                                    <strong class="text-dark small">{{ $transaksi->harga_per_hari ?? 'Rp 350.000' }}</strong>
                                </li>
                                <li class="d-flex justify-content-between align-items-center py-2 border-bottom border-light">
                                    <span class="text-muted small">Durasi</span>
                                    <strong class="text-dark small">{{ $transaksi->durasi_pembayaran ?? '3 hari' }}</strong>
                                </li>
                                <li class="d-flex justify-content-between align-items-center py-2 border-bottom border-light">
                                    <span class="text-muted small">Subtotal</span>
                                    <strong class="text-dark small">{{ $transaksi->subtotal ?? 'Rp 1.575.000' }}</strong>
                                </li>
                                <li class="d-flex justify-content-between align-items-center py-2 border-bottom border-light">
                                    <span class="text-muted small">Biaya antar</span>
                                    <strong class="text-dark small">{{ $transaksi->biaya_antar ?? 'Rp 75.000' }}</strong>
                                </li>
                                <li class="d-flex justify-content-between align-items-center py-2 border-bottom border-light">
                                    <span class="text-muted small">Total pembayaran</span>
                                    <strong class="text-dark small">{{ $transaksi->total_biaya ?? 'Rp 1.650.000' }}</strong>
                                </li>
                                <li class="d-flex justify-content-between align-items-center pt-3">
                                    <span class="text-muted small">Status pembayaran</span>
                                    <span class="badge bg-warning-subtle text-warning border border-warning-subtle px-3 py-1 rounded-pill small fw-medium">{{ $transaksi->status_pembayaran ?? 'Menunggu' }}</span>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <div class="col-12">
            <div class="card border-0 shadow-sm rounded-4 p-4 bg-white h-100 mb-3">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div>
                        <h2 class="fw-bold text-dark mb-1">Dokumen Verifikasi</h2>
                        <p class="text-muted mb-0">Pastikan nama dan wajah sesuai sebelum mengonfirmasi pembayaran.</p>
                    </div>
                    <div>
                        <span class="text-success d-inline-flex align-items-center gap-1 fw-bold">
                            <img src="{{ asset('img_admin/detail-transaksi/shield-check.svg') }}" alt="" /> 2 dokumen diterima
                        </span>
                    </div>
                </div>
                <div class="document-images-container d-flex align-items-center mb-3 gap-3">
                    <div class="doc-box">
                        <img src="{{ asset('img_admin/detail-transaksi/Identity document.png') }}" alt="Foto KTP" />
                    </div>
                    <div class="doc-box">
                        <img src="{{ asset('img_admin/detail-transaksi/Selfie document.png') }}" alt="Foto Selfie KTP" />
                    </div>
                </div>
            </div>
        </div>

        <div class="d-flex align-items-center gap-3 flex-wrap">
            <form action="{{ url('admin/transaksi/' . ($transaksi->id ?? '') . '/konfirmasi-pembayaran') }}" method="POST" class="form-konfirmasi" data-pesan="Konfirmasi pembayaran ini?">
                @csrf
                <button type="submit" class="btn text-white px-4 py-2 rounded-3 fw-medium d-inline-flex align-items-center gap-2 shadow-sm" style="background-color: #ff7a3d;">
                    <img src="{{ asset('img_admin/detail-transaksi/badge-check.svg') }}" alt="" width="20" height="20" />
                    <span>Konfirmasi Pembayaran</span>
                </button>
            </form>

            <form action="{{ url('admin/transaksi/' . ($transaksi->id ?? '') . '/selesaikan') }}" method="POST" class="form-konfirmasi" data-pesan="Selesaikan transaksi ini?">
                @csrf
                <button type="submit" class="btn text-dark px-4 py-2 rounded-3 fw-medium d-inline-flex align-items-center gap-2 shadow-sm bg-white" style="border-color: #cbd5e1;">
                    <img src="{{ asset('img_admin/detail-transaksi/check-circle.svg') }}" alt="" width="20" height="20" />
                    <span>Selesaikan Transaksi</span>
                </button>
            </form>

            <form action="{{ url('admin/transaksi/' . ($transaksi->id ?? '') . '/batalkan') }}" method="POST" class="form-konfirmasi" data-pesan="Batalkan pesanan ini?">
                @csrf
                <button type="submit" class="btn text-danger px-4 py-2 rounded-3 fw-medium shadow-sm" style="color: #dc2626; background-color: #fef2f2; border: 1px solid #fca5a5;">
                    <span>Batalkan Pesanan</span>
                </button>
            </form>

            <a href="{{ url('admin/transaksi') }}" class="btn btn-outline-light px-4 py-2 rounded-3 fw-medium shadow-sm bg-white text-decoration-none" style="border-color: #ff7a00; color:#ff7a00">Kembali</a>
        </div>
    </main>
@endsection

@push('styles')
    <style>
        .doc-box { flex: 0 0 240px; max-width: 240px; }
        .doc-box img { width: 100%; height: 160px; object-fit: cover; border-radius: 12px; box-shadow: 0 2px 8px rgba(11, 29, 51, 0.12); }
    </style>
@endpush

@push('scripts')
    <script src="{{ asset('js/konfirmasi.js') }}"></script>
@endpush
