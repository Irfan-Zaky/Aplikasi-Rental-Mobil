@extends('layouts.admin', ['active' => 'data-mobil'])

@section('title', 'Edit Mobil')

@section('content')
    @php
        $fotoAwal = collect($fotos ?? [])->map(function ($foto) {
            $sumber = data_get($foto, 'src');

            return [
                'id' => data_get($foto, 'id'),
                'nama' => data_get($foto, 'nama'),
                'src' => $sumber && ! preg_match('#^(https?:|/|blob:|data:)#', $sumber) ? asset($sumber) : $sumber,
                'utama' => (bool) data_get($foto, 'utama'),
            ];
        })->values();
        $fotoUtama = $fotoAwal->firstWhere('utama', true) ?? $fotoAwal->first();
    @endphp

    @include('components.admin-header', [
        'kicker' => 'MANAJEMEN ARMADA',
        'judul' => 'Edit Mobil',
        'deskripsi' => 'Atur dan custom data mobil dengan baik',
    ])

    <main>
        <div class="container py-4">
            <div class="row g-4 align-items-start">
                <div class="col-12 col-lg-4">
                    <div class="card border-0 shadow-sm rounded-4 p-4 bg-white">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h5 class="fw-bold m-0 text-dark">Foto Mobil</h5>
                            <span class="badge bg-success-subtle text-success border border-success-subtle px-3 py-2 rounded-pill fw-medium">Aktif</span>
                        </div>

                        <div class="mb-3 text-center">
                            <img src="{{ $fotoUtama['src'] ?? asset('img_admin/edit-mobil/Current vehicle photo.png') }}" alt="Foto Mobil" id="foto-mobil" class="img-fluid rounded-4 object-fit-cover shadow-sm" style="height: 220px; width: 100%;" />
                        </div>
                        <button type="button" id="btn-ganti-foto" class="btn btn-outline-secondary w-100 rounded-pill py-2 d-flex align-items-center justify-content-center gap-2 fw-medium">
                            <img src="{{ asset('img_admin/edit-mobil/image.svg') }}" alt="" width="18" height="18" />
                            <span>Ganti Foto</span>
                        </button>
                    </div>
                </div>

                <div class="col-12 col-lg-8">
                    <div class="card border-0 shadow-sm rounded-4 p-4 bg-white">
                        <form id="form-edit" action="{{ route('admin.data-mobil.update', $mobil) }}" method="POST" enctype="multipart/form-data">
                            @csrf
                            @method('PUT')
                            <input type="hidden" name="foto_utama" id="foto-utama" value="" />
                            <div id="foto-lama-container"></div>

                            <header class="mb-4">
                                <h3 class="fw-bold text-dark">Informasi kendaraan</h3>
                                <p class="text-muted small mb-1">Terakhir diperbarui {{ optional($mobil->updated_at ?? null)->format('d F Y') ?? '28 September 2026' }} oleh Admin Operasional.</p>
                                <p class="text-secondary small fw-medium mb-0">Kode_Mobil.{{ $mobil->kode_mobil }}</p>
                            </header>

                            <div class="row g-3 mb-3">
                                <div class="col-12 col-md-6">
                                    <label for="merk" class="form-label fw-medium small text-dark">Merk</label>
                                    <input type="text" name="merk" id="merk" class="form-control bg-light border-1 py-2 px-3 rounded-pill shadow-sm" value="{{ old('merk', $mobil->merk ?? '') }}" required />
                                </div>

                                <div class="col-12 col-md-6">
                                    <label for="nama-mobil" class="form-label fw-medium small text-dark">Nama Mobil</label>
                                    <input type="text" name="nama" id="nama-mobil" class="form-control bg-light border-1 py-2 px-3 rounded-pill shadow-sm" value="{{ old('nama', $mobil->nama_mobil ?? '') }}" required />
                                </div>

                                <div class="col-12 col-md-6">
                                    <label for="jenis" class="form-label fw-medium small text-dark">Jenis</label>
                                    <select name="jenis" id="jenis" class="form-select bg-light border-1 py-2 px-3 rounded-pill shadow-sm" required>
                                        <option value="">Pilih Jenis</option>
                                        <option value="MPV" @selected(old('jenis', $mobil->jenis ?? '') === 'MPV')>MPV</option>
                                        <option value="SUV" @selected(old('jenis', $mobil->jenis ?? '') === 'SUV')>SUV</option>
                                        <option value="Hatchback" @selected(old('jenis', $mobil->jenis ?? '') === 'Hatchback')>Hatchback</option>
                                        <option value="Sedan" @selected(old('jenis', $mobil->jenis ?? '') === 'Sedan')>Sedan</option>
                                    </select>
                                </div>

                                <div class="col-12 col-md-6">
                                    <label for="status" class="form-label fw-medium small text-dark">Status</label>
                                    <select name="status" id="status" class="form-select bg-light border-1 py-2 px-3 rounded-pill shadow-sm" required>
                                        <option value="">Pilih Status</option>
                                        <option value="Tersedia" @selected(old('status', $mobil->status ?? '') === 'Tersedia')>Tersedia</option>
                                        <option value="Disewa" @selected(old('status', $mobil->status ?? '') === 'Disewa')>Disewa</option>
                                        <option value="Perawatan" @selected(old('status', $mobil->status ?? '') === 'Perawatan')>Perawatan</option>
                                    </select>
                                </div>

                                <div class="col-12 col-md-6">
                                    <label for="harga-sewa" class="form-label fw-medium small text-dark">Harga Sewa / hari</label>
                                    <input type="number" name="harga" id="harga-sewa" class="form-control bg-light border-1 py-2 px-3 rounded-pill shadow-sm" value="{{ old('harga', $mobil->harga_sewa ?? 0) }}" required />
                                </div>

                                <div class="col-12 col-md-6">
                                    <label for="plat-nomor" class="form-label fw-medium small text-dark">Nomor Polisi</label>
                                    <input type="text" name="plat" id="plat-nomor" class="form-control bg-light border-1 py-2 px-3 rounded-pill shadow-sm" value="{{ old('plat', $mobil->nomor_polisi ?? '') }}" required />
                                </div>
                            </div>

                            <div class="mb-4">
                                <label for="spesifikasi" class="form-label fw-medium small text-dark">Spesifikasi</label>
                                <textarea name="spesifikasi" id="spesifikasi" class="form-control bg-light border-1 p-3 rounded-4 shadow-sm" rows="3" required>{{ old('spesifikasi', $mobil->spesifikasi ?? '') }}</textarea>
                            </div>

                            <div class="d-flex align-items-center gap-2">
                                <button type="submit" class="btn text-white px-4 py-2 rounded-pill fw-medium shadow-sm" style="background-color: #ff7a3d; border: none;">Simpan Perubahan</button>
                                <a href="{{ route('admin.data-mobil.index') }}" class="btn btn-outline-secondary px-4 py-2 rounded-pill fw-medium text-dark shadow-sm bg-white text-decoration-none" style="border-color: #cbd5e1;">Batal</a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <div class="modal fade" id="modalFotoMobil" tabindex="-1" aria-labelledby="modalFotoMobilLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable" style="max-width: 860px;">
            <div class="modal-content border-0 rounded-4 shadow">
                <div class="modal-header border-0 align-items-start px-4 pt-4 pb-3">
                    <div>
                        <h4 class="modal-title fw-bold" id="modalFotoMobilLabel" style="color: #0b1d33;">Unggah foto mobil</h4>
                        <p class="text-muted small mb-0 mt-1">Tambahkan beberapa foto dan pilih satu sebagai foto utama.</p>
                    </div>
                    <button type="button" class="btn d-flex align-items-center justify-content-center rounded-3 border-0 flex-shrink-0" data-bs-dismiss="modal" aria-label="Tutup" style="width: 36px; height: 36px; background-color: #f4f7fa; color: #64748b; font-size: 20px; line-height: 1;">&times;</button>
                </div>

                <div style="height: 1px; background-color: #e9eef4;"></div>

                <div class="modal-body px-4 py-4">
                    <input type="file" name="fotos[]" form="form-edit" id="input-foto-modal" class="d-none" accept="image/jpeg,image/png" multiple />

                    <div id="dropzone-foto" class="d-flex flex-wrap align-items-center gap-3 p-3 rounded-4" style="border: 1.5px dashed #cbd5e1; background-color: #f8fafc; cursor: pointer;">
                        <img src="{{ asset('img_admin/tambah-mobil/upload-cloud.svg') }}" alt="" width="52" height="52" class="flex-shrink-0" />
                        <div class="flex-grow-1" style="min-width: 200px;">
                            <p class="fw-semibold mb-1" style="color: #0b1d33; font-size: 14px;">Tarik foto ke sini atau pilih dari perangkat</p>
                            <p class="text-muted small mb-0">Bisa pilih banyak file sekaligus.</p>
                            <p class="text-muted small mb-0">JPG atau PNG. Maks. 8 MB per foto</p>
                        </div>
                        <button type="button" id="btn-tambah-foto" class="btn bg-white rounded-3 px-3 py-2 fw-semibold small flex-shrink-0" style="border: 1px solid #dbe3ec; color: #0b1d33;">+ Tambah foto</button>
                    </div>

                    <div class="d-flex align-items-center gap-2 mt-4 mb-3">
                        <h6 class="fw-bold m-0" style="color: #0b1d33;">Foto dipilih</h6>
                        <span class="badge rounded-pill fw-medium" id="jumlah-foto" style="background-color: #eef2f7; color: #64748b; font-size: 12px; padding: 8px 14px;">0 foto</span>
                    </div>

                    <div class="row g-3" id="daftar-foto" data-foto-awal="{{ json_encode($fotoAwal) }}"></div>

                    <div class="d-flex align-items-start gap-2 mt-4 p-3 rounded-4" style="background-color: #fff4ee;">
                        <svg class="flex-shrink-0 mt-1" width="15" height="15" viewBox="0 0 24 24" fill="#ff7a3d" xmlns="http://www.w3.org/2000/svg"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87L18.18 22 12 18.56 5.82 22 7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>
                        <p class="small mb-0" style="color: #5c6b73;">
                            Foto berbintang akan muncul di card profil mobil.<br />
                            Simpan perubahan setelah memilih foto utama.
                        </p>
                    </div>
                </div>

                <div style="height: 1px; background-color: #e9eef4;"></div>

                <div class="modal-footer border-0 d-flex flex-wrap justify-content-between align-items-center gap-2 px-4 py-3">
                    <p class="small text-muted m-0" id="status-foto">Belum ada foto dipilih</p>
                    <div class="d-flex gap-2">
                        <button type="button" class="btn bg-white px-4 py-2 rounded-3 fw-semibold" data-bs-dismiss="modal" style="border: 1px solid #dbe3ec; color: #0b1d33;">Cancel</button>
                        <button type="button" id="btn-simpan-foto" class="btn px-4 py-2 rounded-3 fw-semibold text-white" style="background-color: #ff7a3d; border: none;">Save</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script src="{{ asset('js/modal-foto-mobil.js') }}"></script>
    <script src="{{ asset('js/edit-mobil.js') }}"></script>
@endpush
