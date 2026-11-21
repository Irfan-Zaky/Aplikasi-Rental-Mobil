@extends('layouts.admin', ['active' => 'data-mobil'])

@section('title', 'Data Mobil')

@section('content')
    @php
        $warnaStatus = [
            'Tersedia' => 'success',
            'Disewa' => 'secondary',
            'Perawatan' => 'warning',
        ];
    @endphp

    @include('components.admin-header', [
        'kicker' => 'MANAJEMEN ARMADA',
        'judul' => 'Data Mobil',
        'deskripsi' => 'Atur dan custom data mobil dengan baik',
    ])

    @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show rounded-4 shadow-sm" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Tutup"></button>
        </div>
    @endif

    <main class="card border-0 shadow-sm rounded-4 p-4 bg-white">
        <section class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h4 class="fw-bold m-0 text-dark">Daftar kendaraan</h4>
                <p class="text-muted small m-0">{{ $mobils->count() }} unit terdaftar &middot; Total Armada</p>
            </div>
            <a href="{{ route('admin.data-mobil.create') }}" class="btn text-white fw-bold px-3 py-2 rounded-3 d-flex align-items-center gap-2 text-decoration-none" style="background-color: #ff7a3d;">
                <span class="fs-5 lh-1">+</span>
                <span>Tambah Mobil</span>
            </a>
        </section>

        <form action="{{ route('admin.data-mobil.index') }}" method="GET" class="d-flex align-items-center mb-4 gap-3">
            <div class="flex-grow-1">
                <input type="search" name="keyword" value="{{ request('keyword') }}" class="form-control bg-light border-1 py-2 px-4 rounded-pill shadow-sm" style="background-color: aliceblue;" placeholder="Cari nama, merk, atau plat nomor — tekan Enter" />
            </div>

            <div style="width: 130px;">
                <select id="jenis" name="jenis" class="form-select bg-light border-1 py-2 px-3 rounded-pill shadow-sm">
                    <option value="">Jenis</option>
                    @foreach (['MPV', 'SUV', 'Hatchback', 'Sedan'] as $jenis)
                        <option value="{{ $jenis }}" @selected(request('jenis') === $jenis)>{{ $jenis }}</option>
                    @endforeach
                </select>
            </div>

            <div style="width: 150px;">
                <select id="status" name="status" class="form-select bg-light border-1 py-2 px-3 rounded-pill shadow-sm">
                    <option value="">Status</option>
                    @foreach (array_keys($warnaStatus) as $status)
                        <option value="{{ $status }}" @selected(request('status') === $status)>{{ $status }}</option>
                    @endforeach
                </select>
            </div>

            <button type="submit" class="visually-hidden">Cari</button>
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
                    @forelse ($mobils as $mobil)
                        @php
                            $kelasStatus = $warnaStatus[$mobil->status] ?? 'secondary';
                            $pathFoto = $mobil->fotoUtama?->path_foto;
                            $fotoMobil = $pathFoto
                                ? \Illuminate\Support\Facades\Storage::disk('public')->url($pathFoto)
                                : asset('img_admin/data-mobil/source/1.jpg');
                        @endphp
                        <tr class="border-bottom">
                            <td class="ps-3">
                                <img src="{{ $fotoMobil }}" alt="Foto {{ $mobil->nama_mobil }}" class="rounded-3 object-fit-cover shadow-sm" width="70" height="45" />
                            </td>
                            <td class="fw-medium">{{ $mobil->merk }}</td>
                            <td class="fw-bold">{{ $mobil->nama_mobil }}</td>
                            <td class="text-secondary">{{ $mobil->jenis }}</td>
                            <td class="fw-medium">Rp {{ number_format($mobil->harga_sewa, 0, ',', '.') }}</td>
                            <td>
                                <span class="badge bg-{{ $kelasStatus }} bg-opacity-10 text-{{ $kelasStatus }} border border-{{ $kelasStatus }} px-3 py-2 rounded-pill fw-medium">{{ $mobil->status }}</span>
                            </td>
                            <td class="pe-3">
                                <div class="d-flex gap-1">
                                    <a href="{{ route('admin.data-mobil.show', $mobil) }}" class="btn btn-sm px-3 rounded-3 text-decoration-none" style="background-color: transparent; border: 1px solid #6c757d; color: black;">Detail</a>
                                    <a href="{{ route('admin.data-mobil.edit', $mobil) }}" class="btn btn-sm px-3 rounded-3 text-decoration-none" style="background-color: transparent; border: 1px solid #6c757d; color: black;">Edit</a>
                                    <form action="{{ route('admin.data-mobil.destroy', $mobil) }}" method="POST" class="form-konfirmasi" data-pesan="Yakin ingin menghapus mobil ini?">
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
