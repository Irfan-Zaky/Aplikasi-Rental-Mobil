@extends('layouts.customer')

@section('title', 'Data Diri')

@section('body-class', '')

@section('body-style', 'color: #0b1d33')

@section('content')
    @php
        $dokumenDraft = $dokumenDraft ?? [];
        $adaFotoKtp = filled($dokumenDraft['ktp'] ?? null);
        $adaFotoSim = filled($dokumenDraft['sim'] ?? null);
        $adaSwafoto = filled($dokumenDraft['selfie'] ?? null);
    @endphp

    <nav class="navbar bg-white py-3 border-bottom">
        <div class="container-fluid position-relative d-flex justify-content-center align-items-center px-3">
            <a href="{{ route($selfDriver ? 'customer.detail-layanan.lepas-kunci' : 'customer.detail-layanan.sopir') }}"
                aria-label="Kembali ke Detail Layanan"
                class="position-absolute start-0 ms-3 d-flex align-items-center justify-content-center rounded-circle bg-light text-decoration-none text-dark fw-bold fs-3 pb-1"
                style="width: 40px; height: 40px; color: #0b1d33;">&lsaquo;</a>
            <h2 class="fs-3 fw-bold m-0" style="color: #0b1d33;">Data Diri</h2>
        </div>
    </nav>

    <main>
        <h2 class="fs-4 fw-bold px-3 my-2">Lengkapi data penyewa</h2>
        <p class="text-secondary small px-3 my-1">Pastikan data sesuai dokumen resmi untuk proses verifikasi.</p>

        <form action="{{ route('customer.data-diri.store') }}" method="POST" enctype="multipart/form-data"
            class="my-4">
            @csrf

            <div class="mb-4 px-3">
                <label for="nama-lengkap" class="h8 fw-bold">Nama Lengkap</label>
                <input type="text" class="form-control mt-1" style="border-color: #0b1d33" id="nama-lengkap"
                    name="nama_lengkap" placeholder="Masukkan nama lengkap"
                    value="{{ old('nama_lengkap', $namaPenyewa) }}" required />
            </div>

            <div class="mb-4 px-3">
                <label for="nomor-hp" class="h8 fw-bold">Nomor HP</label>
                <input type="tel" class="form-control mt-1" style="border-color: #0b1d33" id="nomor-hp"
                    name="nomor_hp" placeholder="08xxxxxxxxxx"
                    value="{{ old('nomor_hp', $nomorHpPenyewa) }}" required />
            </div>

            <div class="mb-4 px-3">
                <label for="nik" class="h8 fw-bold">NIK</label>
                <input type="text" class="form-control mt-1" style="border-color: #0b1d33" id="nik"
                    name="nik" placeholder="16 digit nomor KTP" inputmode="numeric" value="{{ old('nik') }}"
                    required />
            </div>

            <div class="mb-4 px-3">
                <p class="form-label fw-bold mb-2" style="color: #0b1d33;">Foto KTP</p>
                <label for="foto-KTP" class="w-100 bg-light rounded-4 overflow-hidden border d-flex align-items-center justify-content-center cursor-pointer"
                    style="border-color: #a0aec0 !important; max-width: 570px; aspect-ratio: 19 / 6; cursor: pointer;">
                    <img @if ($adaFotoKtp) src="{{ route('customer.data-diri.dokumen', ['jenis' => 'ktp']) }}" @endif
                        alt="Pratinjau Foto KTP" data-pratinjau="ktp"
                        class="w-100 h-100 object-fit-contain {{ $adaFotoKtp ? '' : 'd-none' }}" />
                    <span class="text-center text-secondary small fw-medium {{ $adaFotoKtp ? 'd-none' : '' }}"
                        data-placeholder="ktp">
                        <img src="{{ asset('img/data-diri/upload.svg') }}" alt="Upload Icon" class="mb-2" width="24"
                            height="24" />
                        <span class="d-block">Unggah / ambil foto</span>
                    </span>
                    <input type="file" class="d-none" id="foto-KTP" name="foto_ktp" accept="image/*"
                        data-unggah="ktp" required />
                </label>
                <p class="text-secondary small mt-1 mb-0" data-nama-file="ktp">
                    {{ $adaFotoKtp ? 'Klik gambar untuk mengganti foto' : '' }}</p>
                @error('foto_ktp')
                    <div class="text-danger small mt-1">{{ $message }}</div>
                @enderror
            </div>

            <div class="mb-4 px-3">
                <p class="form-label fw-bold mb-2" style="color: #0b1d33;">Foto SIM</p>
                <label for="foto-SIM" class="w-100 bg-light rounded-4 overflow-hidden border d-flex align-items-center justify-content-center cursor-pointer"
                    style="border-color: #a0aec0 !important; max-width: 570px; aspect-ratio: 19 / 6; cursor: pointer;">
                    <img @if ($adaFotoSim) src="{{ route('customer.data-diri.dokumen', ['jenis' => 'sim']) }}" @endif
                        alt="Pratinjau Foto SIM" data-pratinjau="sim"
                        class="w-100 h-100 object-fit-contain {{ $adaFotoSim ? '' : 'd-none' }}" />
                    <span class="text-center text-secondary small fw-medium {{ $adaFotoSim ? 'd-none' : '' }}"
                        data-placeholder="sim">
                        <img src="{{ asset('img/data-diri/upload.svg') }}" alt="Upload Icon" class="mb-2" width="24"
                            height="24" />
                        <span class="d-block">Unggah / ambil foto</span>
                    </span>
                    <input type="file" class="d-none" id="foto-SIM" name="foto_sim" accept="image/*"
                        data-unggah="sim" required />
                </label>
                <p class="text-secondary small mt-1 mb-0" data-nama-file="sim">
                    {{ $adaFotoSim ? 'Klik gambar untuk mengganti foto' : '' }}</p>
                @error('foto_sim')
                    <div class="text-danger small mt-1">{{ $message }}</div>
                @enderror
            </div>

            <div class="mb-4 px-3">
                <p class="form-label fw-bold mb-2" style="color: #0b1d33;">Swafoto (Selfie)</p>
                <label for="swafoto" class="w-100 bg-light rounded-4 overflow-hidden border d-flex align-items-center justify-content-center cursor-pointer"
                    style="border-color: #a0aec0 !important; max-width: 300px; aspect-ratio: 3 / 4; cursor: pointer;">
                    <img @if ($adaSwafoto) src="{{ route('customer.data-diri.dokumen', ['jenis' => 'selfie']) }}" @endif
                        alt="Pratinjau Swafoto" data-pratinjau="selfie"
                        class="w-100 h-100 object-fit-contain {{ $adaSwafoto ? '' : 'd-none' }}" />
                    <span class="text-center text-secondary small fw-medium {{ $adaSwafoto ? 'd-none' : '' }}"
                        data-placeholder="selfie">
                        <img src="{{ asset('img/data-diri/upload.svg') }}" alt="Upload Icon" class="mb-2" width="24"
                            height="24" />
                        <span class="d-block">Unggah / ambil foto</span>
                    </span>
                    <input type="file" class="d-none" id="swafoto" name="swafoto" accept="image/*"
                        data-unggah="selfie" required />
                </label>
                <p class="text-secondary small mt-1 mb-0" data-nama-file="selfie">
                    {{ $adaSwafoto ? 'Klik gambar untuk mengganti foto' : '' }}</p>
                @error('swafoto')
                    <div class="text-danger small mt-1">{{ $message }}</div>
                @enderror
            </div>

            <div class="p-3 rounded-4 d-flex align-items-center gap-3 mb-4" style="background-color: #eef7fc;">
                <img src="{{ asset('img/data-diri/icon-data-anda-terenkripsi.svg') }}" alt="Ikon Enkripsi"
                    class="flex-shrink-0" width="24" height="24" />
                <p class="text-secondary small mb-0 fw-medium"> Data Anda dienkripsi dan hanya digunakan untuk verifikasi
                    penyewaan.</p>
            </div>

            <div class="fixed-bottom bg-white py-3 px-3 border-top shadow-sm">
                <div class="container p-0">
                    <button type="submit"
                        class="btn text-white w-100 py-3 rounded-pill fw-bold d-flex align-items-center justify-content-center gap-2"
                        style="background-color: #0b1d33;"> Lanjut
                        <img src="{{ asset('img/data-diri/lanjut.svg') }}" alt="lanjut" width="18"
                            height="18" />
                    </button>
                </div>
            </div>
        </form>
    </main>
@endsection

@push('scripts')
    <script>
        (() => {
            const jenisDokumen = ['ktp', 'sim', 'selfie'];
            const urlObjek = {};

            jenisDokumen.forEach((jenis) => {
                const input = document.querySelector(`[data-unggah="${jenis}"]`);
                const gambar = document.querySelector(`[data-pratinjau="${jenis}"]`);
                const placeholder = document.querySelector(`[data-placeholder="${jenis}"]`);
                const keterangan = document.querySelector(`[data-nama-file="${jenis}"]`);

                if (!input || !gambar || !placeholder) {
                    return;
                }

                input.addEventListener('change', () => {
                    const berkas = input.files && input.files[0];

                    if (!berkas) {
                        return;
                    }

                    if (urlObjek[jenis]) {
                        URL.revokeObjectURL(urlObjek[jenis]);
                    }

                    urlObjek[jenis] = URL.createObjectURL(berkas);
                    gambar.src = urlObjek[jenis];
                    gambar.classList.remove('d-none');
                    placeholder.classList.add('d-none');

                    if (keterangan) {
                        keterangan.textContent = `Ganti: ${berkas.name}`;
                    }
                });
            });

            window.addEventListener('beforeunload', () => {
                Object.values(urlObjek).forEach((url) => URL.revokeObjectURL(url));
            });
        })();
    </script>
@endpush
