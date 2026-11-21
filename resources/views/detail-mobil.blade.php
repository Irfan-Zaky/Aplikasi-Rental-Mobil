@extends('layouts.admin', ['active' => 'data-mobil'])

@section('title', 'Detail Mobil')

@section('content')
    @php
        $fotos = $fotos ?? [];

        if (count($fotos) === 0) {
            $fotos = [['nama' => 'Foto mobil 1', 'src' => asset('img_admin/data-mobil/source/1.jpg'), 'utama' => true]];
        }
    @endphp

    @include('components.admin-header', [
        'kicker' => 'MANAJEMEN ARMADA',
        'judul' => 'Detail Mobil',
        'deskripsi' => 'Atur dan custom data mobil dengan baik',
    ])

    <main>
        <div class="container py-4">
            <div class="row g-4 align-items-start">
                <div class="col-12 col-lg-4">
                    <div class="card border-0 shadow-sm rounded-4 p-4 bg-white">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h5 class="fw-bold m-0 text-dark">Foto yang lain</h5>
                            <span class="badge bg-success-subtle text-success border border-success-subtle px-3 py-2 rounded-pill fw-medium">Aktif</span>
                        </div>

                        <div id="carouselFotoMobil" class="carousel slide mb-3 rounded-4 overflow-hidden shadow-sm" data-bs-ride="carousel" data-bs-interval="3500" data-bs-touch="true">
                            <div class="carousel-indicators">
                                @foreach ($fotos as $foto)
                                    <button type="button" data-bs-target="#carouselFotoMobil" data-bs-slide-to="{{ $loop->index }}" class="{{ $loop->first ? 'active' : '' }}" @if ($loop->first) aria-current="true" @endif aria-label="Foto {{ $loop->iteration }}"></button>
                                @endforeach
                            </div>

                            <div class="carousel-inner">
                                @foreach ($fotos as $foto)
                                    <div class="carousel-item {{ $loop->first ? 'active' : '' }}">
                                        <img src="{{ data_get($foto, 'src') }}" class="d-block w-100 object-fit-cover" style="height: 220px;" alt="{{ data_get($foto, 'nama', 'Foto mobil ' . $loop->iteration) }}" />
                                    </div>
                                @endforeach
                            </div>

                            <button class="carousel-control-prev" type="button" data-bs-target="#carouselFotoMobil" data-bs-slide="prev">
                                <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                                <span class="visually-hidden">Sebelumnya</span>
                            </button>
                            <button class="carousel-control-next" type="button" data-bs-target="#carouselFotoMobil" data-bs-slide="next">
                                <span class="carousel-control-next-icon" aria-hidden="true"></span>
                                <span class="visually-hidden">Berikutnya</span>
                            </button>
                        </div>
                    </div>
                </div>

                <div class="col-12 col-lg-8">
                    <div class="card border-0 shadow-sm rounded-4 p-4 bg-white">
                        <form action="{{ route('admin.data-mobil.show', $mobil) }}" method="POST">
                            @csrf
                            <header class="mb-4">
                                <h3 class="fw-bold text-dark">Informasi kendaraan</h3>
                                <p class="text-muted small mb-1">Terakhir diperbarui {{ optional($mobil->updated_at ?? null)->format('d F Y') ?? '28 September 2026' }} oleh Admin Operasional.</p>
                                <p class="text-secondary small fw-medium mb-0" id="kode-mobil">Kode_Mobil.{{ $mobil->kode_mobil }}</p>
                            </header>

                            <div class="row g-3 mb-3">
                                <div class="col-12 col-md-6">
                                    <label for="merk" class="form-label fw-medium small text-dark">Merk</label>
                                    <input type="text" name="merk" id="merk" class="form-control bg-light border-1 py-2 px-3 rounded-pill shadow-sm" value="{{ $mobil->merk ?? '' }}" readonly />
                                </div>

                                <div class="col-12 col-md-6">
                                    <label for="nama-mobil" class="form-label fw-medium small text-dark">Nama Mobil</label>
                                    <input type="text" name="nama" id="nama-mobil" class="form-control bg-light border-1 py-2 px-3 rounded-pill shadow-sm" value="{{ $mobil->nama_mobil ?? '' }}" readonly />
                                </div>

                                <div class="col-12 col-md-6">
                                    <label for="jenis" class="form-label fw-medium small text-dark">Jenis</label>
                                    <select name="jenis" id="jenis" class="form-select bg-light border-1 py-2 px-3 rounded-pill shadow-sm" disabled>
                                        <option value="">Pilih Jenis</option>
                                        <option value="MPV" @selected(($mobil->jenis ?? '') === 'MPV')>MPV</option>
                                        <option value="SUV" @selected(($mobil->jenis ?? '') === 'SUV')>SUV</option>
                                        <option value="Hatchback" @selected(($mobil->jenis ?? '') === 'Hatchback')>Hatchback</option>
                                        <option value="Sedan" @selected(($mobil->jenis ?? '') === 'Sedan')>Sedan</option>
                                    </select>
                                </div>

                                <div class="col-12 col-md-6">
                                    <label for="status" class="form-label fw-medium small text-dark">Status</label>
                                    <select name="status" id="status" class="form-select bg-light border-1 py-2 px-3 rounded-pill shadow-sm" disabled>
                                        <option value="">Pilih Status</option>
                                        <option value="Tersedia" @selected(($mobil->status ?? '') === 'Tersedia')>Tersedia</option>
                                        <option value="Disewa" @selected(($mobil->status ?? '') === 'Disewa')>Disewa</option>
                                        <option value="Perawatan" @selected(($mobil->status ?? '') === 'Perawatan')>Perawatan</option>
                                    </select>
                                </div>

                                <div class="col-12 col-md-6">
                                    <label for="harga-sewa" class="form-label fw-medium small text-dark">Harga Sewa / hari</label>
                                    <input type="text" name="harga" id="harga-sewa" class="form-control bg-light border-1 py-2 px-3 rounded-pill shadow-sm" value="Rp {{ number_format($mobil->harga_sewa, 0, ',', '.') }}" readonly />
                                </div>

                                <div class="col-12 col-md-6">
                                    <label for="plat-nomor" class="form-label fw-medium small text-dark">Nomor Polisi</label>
                                    <input type="text" name="plat" id="plat-nomor" class="form-control bg-light border-1 py-2 px-3 rounded-pill shadow-sm" value="{{ $mobil->nomor_polisi ?? '' }}" readonly />
                                </div>
                            </div>

                            <div class="mb-4">
                                <label for="spesifikasi" class="form-label fw-medium small text-dark">Spesifikasi</label>
                                <textarea name="spesifikasi" id="spesifikasi" class="form-control bg-light border-1 p-3 rounded-4 shadow-sm" rows="3" readonly>{{ $mobil->spesifikasi ?? '' }}</textarea>
                            </div>

                            <div class="d-flex align-items-center gap-2">
                                <a href="{{ route('admin.data-mobil.index') }}" class="btn text-white px-4 py-2 rounded-pill fw-medium shadow-sm text-decoration-none" style="background-color: #ff7a3d;">Kembali</a>
                                <a href="{{ route('admin.data-mobil.edit', $mobil) }}" id="btn-edit-mobil" class="btn btn-outline-secondary px-4 py-2 rounded-pill fw-medium shadow-sm text-decoration-none">Edit</a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </main>
@endsection

@push('scripts')
    <script src="{{ asset('js/carousel-foto.js') }}"></script>
@endpush
