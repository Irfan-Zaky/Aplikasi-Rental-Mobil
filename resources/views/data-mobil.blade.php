@extends('layouts.admin', ['active' => 'data-mobil'])

@section('title', 'Data Mobil')

@section('content')
    @php
        $warnaStatus = [
            'Tersedia' => 'success',
            'Disewa' => 'secondary',
            'Perbaikan' => 'warning',
            'Nonaktif' => 'danger',
        ];
    @endphp

    @include('components.admin-header', [
        'kicker' => 'MANAJEMEN ARMADA',
        'judul' => 'Data Mobil',
        'deskripsi' => 'Atur dan custom data mobil dengan baik',
    ])

    <main class="card border-0 shadow-sm rounded-4 p-4 bg-white">
        <section class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h4 class="fw-bold m-0 text-dark">Daftar kendaraan</h4>
                <p class="text-muted small m-0">{{ count($mobils ?? []) }} unit terdaftar &middot; Total Armada</p>
            </div>
            <a href="{{ url('admin/data-mobil/create') }}" class="btn text-white fw-bold px-3 py-2 rounded-3 d-flex align-items-center gap-2 text-decoration-none" style="background-color: #ff7a3d;">
                <span class="fs-5 lh-1">+</span>
                <span>Tambah Mobil</span>
            </a>
        </section>

        <form action="{{ url('admin/data-mobil') }}" method="GET" class="d-flex align-items-center mb-4 gap-5">
            <div class="flex-grow-1 gap-3">
                <input type="search" name="keyword" value="{{ request('keyword') }}" class="form-control bg-light border-1 py-2 px-4 rounded-pill shadow-sm" style="background-color: aliceblue;" placeholder="Cari nama, merk, atau plat nomor" />
            </div>

            <div style="width: 140px;">
                <select id="merk" name="merk" class="form-select bg-light border-1 py-2 px-3 rounded-pill shadow-sm">
                    <option value="">Merk</option>
                    <option value="avanza" @selected(request('merk') === 'avanza')>Toyota Avanza</option>
                    <option value="xenia" @selected(request('merk') === 'xenia')>Daihatsu Xenia</option>
                    <option value="brio" @selected(request('merk') === 'brio')>Honda Brio</option>
                    <option value="brv" @selected(request('merk') === 'brv')>Honda BR-V</option>
                </select>
            </div>

            <div style="width: 130px;">
                <select id="jenis" name="jenis" class="form-select bg-light border-1 py-2 px-3 rounded-pill shadow-sm">
                    <option value="">Jenis</option>
                    <option value="MPV" @selected(request('jenis') === 'MPV')>MPV</option>
                    <option value="SUV" @selected(request('jenis') === 'SUV')>SUV</option>
                </select>
            </div>

            <div style="width: 150px;">
                <select id="status" name="status" class="form-select bg-light border-1 py-2 px-3 rounded-pill shadow-sm">
                    <option value="">Status</option>
                    <option value="Tersedia" @selected(request('status') === 'Tersedia')>Tersedia</option>
                    <option value="Disewa" @selected(request('status') === 'Disewa')>Disewa</option>
                    <option value="Perbaikan" @selected(request('status') === 'Perbaikan')>Perbaikan</option>
                    <option value="Nonaktif" @selected(request('status') === 'Nonaktif')>Nonaktif</option>
                </select>
            </div>
        </form>

        <div class="table-responsive rounded-top-4 overflow-hidden shadow-sm">
            <table class="table align-middle mb-0 border-0">
                <thead style="background-color: #0d1b2a;">
                    <tr>
                        <th class="py-3 ps-3 border-0 text-white" style="background-color: #0d1b2a; color: white !important;">FOTO</th>
                        <th class="py-3 border-0" style="background-color: #0d1b2a; color: white !important;">MERK</th>
                        <th class="py-3 border-0" style="background-color: #0d1b2a; color: white !important;">NAMA MOBIL</th>
                        <th class="py-3 border-0" style="background-color: #0d1b2a; color: white !important;">JENIS</th>
                        <th class="py-3 border-0" style="background-color: #0d1b2a; color: white !important;">HARGA / HARI</th>
                        <th class="py-3 border-0" style="background-color: #0d1b2a; color: white !important;">STATUS</th>
                        <th class="py-3 pe-3 border-0 text-white" style="background-color: #0d1b2a; color: white !important;">AKSI</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($mobils ?? [] as $mobil)
                        @php
                            $kelasStatus = $warnaStatus[$mobil->status] ?? 'secondary';
                            $fotoMobil = $mobil->foto ? asset($mobil->foto) : asset('img_admin/data-mobil/source/1.jpg');
                        @endphp
                        <tr class="border-bottom">
                            <td class="ps-3">
                                <img src="{{ $fotoMobil }}" alt="" class="rounded-3 object-fit-cover shadow-sm" width="70" height="45" />
                            </td>
                            <td class="fw-medium">{{ $mobil->merk }}</td>
                            <td class="fw-bold">{{ $mobil->nama }}</td>
                            <td class="text-secondary">{{ $mobil->jenis }}</td>
                            <td class="fw-medium">
                                {{ is_numeric($mobil->harga ?? null) ? 'Rp ' . number_format($mobil->harga, 0, ',', '.') : ($mobil->harga ?: '-') }}
                            </td>
                            <td>
                                <span class="badge bg-{{ $kelasStatus }} bg-opacity-10 text-{{ $kelasStatus }} border border-{{ $kelasStatus }} px-3 py-2 rounded-pill fw-medium">{{ $mobil->status }}</span>
                            </td>
                            <td class="pe-3">
                                <div class="d-flex gap-1">
                                    <a href="{{ url('admin/data-mobil/' . $mobil->id) }}" class="btn btn-sm px-3 rounded-3 text-decoration-none" style="background-color: transparent; border: 1px solid #6c757d; color: black;">Detail</a>
                                    <a href="{{ url('admin/data-mobil/' . $mobil->id . '/edit') }}" class="btn btn-sm px-3 rounded-3 text-decoration-none" style="background-color: transparent; border: 1px solid #6c757d; color: black;">Edit</a>
                                    <form action="{{ url('admin/data-mobil/' . $mobil->id) }}" method="POST" class="form-konfirmasi" data-pesan="Yakin ingin menghapus mobil ini?">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm px-3 rounded-3" style="background-color: transparent; border: 1px solid #dc3545; color: #dc3545;">Hapus</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center text-muted small py-4">Tidak ada mobil yang cocok dengan pencarian.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </main>
@endsection

@push('scripts')
    <script src="{{ asset('js/konfirmasi.js') }}"></script>
@endpush
