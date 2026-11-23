@extends('layouts.customer')

@section('title', 'Katalog')

@push('styles')
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <link rel="stylesheet" type="text/css" href="https://npmcdn.com/flatpickr/dist/themes/dark.css">
@endpush

@section('content')
    @php
        $cars = $cars ?? collect();
        $keyword = $keyword ?? request('keyword', '');
        $selectedMerk = $selectedMerk ?? request('merk', '');
        $selectedJenis = $selectedJenis ?? request('jenis', '');
        $hargaMin = $hargaMin ?? request('harga_min');
        $hargaMax = $hargaMax ?? request('harga_max');
        $isFilterAktif = $isFilterAktif ?? (
            filled($keyword) || filled($selectedMerk) || filled($selectedJenis) || filled($hargaMin) || filled($hargaMax)
        );
        $jumlahCar = $cars->count();
    @endphp

    <nav class="navbar px-3 w-100" style="background-color: white">
        <div class="container-fluid px-2">
            <div class="w-100 px-3 py-1 d-flex align-items-center">
                <h1 class="fw-bold m-0 fs-3" style="color: #0b1d33">Katalog Car</h1>
            </div>
        </div>
    </nav>

    <main class="overflow-hidden">
        <section class="py-4">
            <div class="container">
                <div class="row justify-content-between">
                    <div class="col-12 col-md-8 col-lg-6">
                        <div class="p-3 rounded-4 border-0 mb-3" style="background-color: white; max-width: 480px;">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <div class="d-flex align-items-center gap-2">
                                    <i class="bi bi-calendar-event text-primary fs-5"></i>
                                    <h2 class="h6 fw-bold mb-0 text-dark">Tanggal sewa</h2>
                                </div>

                                <button id="btn-ubah-tanggal" type="button"
                                    class="btn btn-link p-0 text-primary fw-bold text-decoration-none small">Ubah</button>
                                <input type="text" id="jangkar-kalender" class="d-none">
                            </div>
                            <div class="d-flex align-items-center gap-2">
                                <div class="bg-white rounded-3 p-3 flex-fill">
                                    <p class="text-secondary small mb-1">Mulai</p>
                                    <p id="teks-tgl-mulai" class="fw-bold text-dark mb-0">
                                        {{ $tanggalMulai ?? 'Pilih tanggal' }}</p>
                                </div>
                                <i class="bi bi-arrow-right text-primary fs-5 px-1"></i>
                                <div class="bg-white rounded-3 p-3 flex-fill">
                                    <p class="text-secondary small mb-1">Selesai</p>
                                    <p id="teks-tgl-selesai" class="fw-bold text-dark mb-0">
                                        {{ $tanggalSelesai ?? 'Pilih tanggal' }}</p>
                                </div>
                            </div>
                           
                        </div>
                    </div>
                </div>

                {{-- Satu form untuk pencarian dan seluruh filter, supaya pilihan tidak saling menimpa. --}}
                <form id="form-filter-katalog" action="{{ route('customer.katalog') }}" method="GET"
                    class="mb-3" style="max-width: 480px;">

                    <div class="d-flex align-items-center gap-2 overflow-x-auto text-nowrap pb-2">
                        <a href="{{ route('customer.katalog') }}" id="btn-semua"
                            class="btn {{ !$isFilterAktif ? 'btn-dark' : 'btn-outline-dark bg-white' }} rounded-pill px-4 py-2 fw-medium text-decoration-none">
                            Semua
                        </a>
                        <button id="btn-merk" type="button" data-aktif="{{ filled($selectedMerk) ? 'true' : 'false' }}"
                            data-bs-toggle="modal" data-bs-target="#modal-filter-merk"
                            class="btn {{ filled($selectedMerk) ? 'btn-dark' : 'btn-outline-dark bg-white' }} rounded-pill px-4 py-2 fw-medium d-inline-flex align-items-center gap-1"
                            aria-controls="modal-filter-merk">
                            <span>Merk</span>
                            @if(filled($selectedMerk))
                                <span class="badge bg-light text-dark ms-1">{{ $selectedMerk }}</span>
                            @endif
                        </button>
                        <button id="btn-jenis" type="button" data-aktif="{{ filled($selectedJenis) ? 'true' : 'false' }}"
                            data-bs-toggle="modal" data-bs-target="#modal-filter-jenis"
                            class="btn {{ filled($selectedJenis) ? 'btn-dark' : 'btn-outline-dark bg-white' }} rounded-pill px-4 py-2 fw-medium d-inline-flex align-items-center gap-1"
                            aria-controls="modal-filter-jenis">
                            <span>Jenis</span>
                            @if(filled($selectedJenis))
                                <span class="badge bg-light text-dark ms-1">{{ $selectedJenis }}</span>
                            @endif
                        </button>
                        <button id="btn-harga" type="button"
                            data-aktif="{{ (filled($hargaMin) || filled($hargaMax)) ? 'true' : 'false' }}"
                            data-bs-toggle="modal" data-bs-target="#modal-filter-harga"
                            class="btn {{ (filled($hargaMin) || filled($hargaMax)) ? 'btn-dark' : 'btn-outline-dark bg-white' }} rounded-pill px-4 py-2 fw-medium d-inline-flex align-items-center gap-1"
                            aria-controls="modal-filter-harga">
                            <span>Harga</span>
                            @if(filled($hargaMin) || filled($hargaMax))
                                <span class="badge bg-light text-dark ms-1">Aktif</span>
                            @endif
                        </button>
                    </div>
                </form>

                {{-- Modal filter Merk: input + datalist, Enter langsung mencari. --}}
                <div class="modal fade" id="modal-filter-merk" tabindex="-1" aria-labelledby="judul-modal-merk"
                    aria-hidden="true">
                    <div class="modal-dialog modal-dialog-centered">
                        <div class="modal-content rounded-4 border-0 shadow">
                            <div class="modal-header border-0 pb-0">
                                <h5 class="modal-title fw-bold text-dark" id="judul-modal-merk">Filter Berdasarkan Merk</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                            </div>
                            <div class="modal-body pt-2">
                                <label for="input-filter-merk" class="form-label small text-muted mb-1">
                                    Ketik atau pilih merk, lalu tekan <kbd>Enter</kbd>
                                </label>
                                <input type="text" id="input-filter-merk" name="merk" list="daftar-merk"
                                    form="form-filter-katalog" value="{{ $selectedMerk }}" autocomplete="off"
                                    class="form-control bg-light border-1 py-2 px-3 rounded-3 shadow-sm"
                                    placeholder="Contoh: Toyota, Honda, Daihatsu..." />
                                <datalist id="daftar-merk">
                                    @foreach ($daftarMerk ?? [] as $m)
                                        <option value="{{ $m }}"></option>
                                    @endforeach
                                </datalist>
                            </div>
                            <div class="modal-footer border-0 pt-0">
                                @if(filled($selectedMerk))
                                    <a href="{{ request()->fullUrlWithQuery(['merk' => null]) }}"
                                        class="btn btn-outline-secondary rounded-pill px-3 py-2 fw-medium text-decoration-none me-auto">Hapus</a>
                                @endif
                                <button type="button" class="btn btn-outline-secondary rounded-pill px-4 py-2 fw-medium"
                                    data-bs-dismiss="modal">Batal</button>
                                <button type="submit" form="form-filter-katalog"
                                    class="btn text-white rounded-pill px-4 py-2 fw-medium"
                                    style="background-color: #ff7a3d;">Terapkan</button>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Modal filter Jenis: input + datalist, Enter langsung mencari. --}}
                <div class="modal fade" id="modal-filter-jenis" tabindex="-1" aria-labelledby="judul-modal-jenis"
                    aria-hidden="true">
                    <div class="modal-dialog modal-dialog-centered">
                        <div class="modal-content rounded-4 border-0 shadow">
                            <div class="modal-header border-0 pb-0">
                                <h5 class="modal-title fw-bold text-dark" id="judul-modal-jenis">Filter Berdasarkan Jenis</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                            </div>
                            <div class="modal-body pt-2">
                                <label for="input-filter-jenis" class="form-label small text-muted mb-1">
                                    Ketik atau pilih jenis, lalu tekan <kbd>Enter</kbd>
                                </label>
                                <input type="text" id="input-filter-jenis" name="jenis" list="daftar-jenis"
                                    form="form-filter-katalog" value="{{ $selectedJenis }}" autocomplete="off"
                                    class="form-control bg-light border-1 py-2 px-3 rounded-3 shadow-sm"
                                    placeholder="Contoh: SUV, MPV, Sedan..." />
                                <datalist id="daftar-jenis">
                                    @foreach ($daftarJenis ?? ['MPV', 'SUV', 'Hatchback', 'Sedan'] as $itemJenis)
                                        <option value="{{ $itemJenis }}"></option>
                                    @endforeach
                                </datalist>
                            </div>
                            <div class="modal-footer border-0 pt-0">
                                @if(filled($selectedJenis))
                                    <a href="{{ request()->fullUrlWithQuery(['jenis' => null]) }}"
                                        class="btn btn-outline-secondary rounded-pill px-3 py-2 fw-medium text-decoration-none me-auto">Hapus</a>
                                @endif
                                <button type="button" class="btn btn-outline-secondary rounded-pill px-4 py-2 fw-medium"
                                    data-bs-dismiss="modal">Batal</button>
                                <button type="submit" form="form-filter-katalog"
                                    class="btn text-white rounded-pill px-4 py-2 fw-medium"
                                    style="background-color: #ff7a3d;">Terapkan</button>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Modal filter Harga: batas terendah dan tertinggi, Enter langsung mencari. --}}
                <div class="modal fade" id="modal-filter-harga" tabindex="-1" aria-labelledby="judul-modal-harga"
                    aria-hidden="true">
                    <div class="modal-dialog modal-dialog-centered">
                        <div class="modal-content rounded-4 border-0 shadow">
                            <div class="modal-header border-0 pb-0">
                                <h5 class="modal-title fw-bold text-dark" id="judul-modal-harga">Filter Berdasarkan Harga</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                            </div>
                            <div class="modal-body pt-2">
                                <p class="small text-muted mb-3">Masukkan batas harga sewa per hari, lalu tekan <kbd>Enter</kbd>.</p>
                                <div class="row g-2">
                                    <div class="col-6">
                                        <label for="input-harga-min" class="form-label small text-muted mb-1">Batas Terendah (Rp)</label>
                                        <input type="number" id="input-harga-min" name="harga_min"
                                            form="form-filter-katalog"
                                            value="{{ filled($hargaMin) ? $hargaMin : '' }}"
                                            class="form-control bg-light border-1 py-2 px-3 rounded-3 shadow-sm"
                                            placeholder="Min: 300000" min="0" step="1000" />
                                    </div>
                                    <div class="col-6">
                                        <label for="input-harga-max" class="form-label small text-muted mb-1">Batas Tertinggi (Rp)</label>
                                        <input type="number" id="input-harga-max" name="harga_max"
                                            form="form-filter-katalog"
                                            value="{{ filled($hargaMax) ? $hargaMax : '' }}"
                                            class="form-control bg-light border-1 py-2 px-3 rounded-3 shadow-sm"
                                            placeholder="Max: 800000" min="0" step="1000" />
                                    </div>
                                </div>
                            </div>
                            <div class="modal-footer border-0 pt-0">
                                @if(filled($hargaMin) || filled($hargaMax))
                                    <a href="{{ request()->fullUrlWithQuery(['harga_min' => null, 'harga_max' => null]) }}"
                                        class="btn btn-outline-secondary rounded-pill px-3 py-2 fw-medium text-decoration-none me-auto">Hapus</a>
                                @endif
                                <button type="button" class="btn btn-outline-secondary rounded-pill px-4 py-2 fw-medium"
                                    data-bs-dismiss="modal">Batal</button>
                                <button type="submit" form="form-filter-katalog"
                                    class="btn text-white rounded-pill px-4 py-2 fw-medium"
                                    style="background-color: #ff7a3d;">Terapkan</button>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Tag / Badge Filter yang Sedang Aktif --}}
                @if($isFilterAktif)
                    <div class="d-flex align-items-center gap-2 flex-wrap mb-3">
                        <span class="small text-muted fw-semibold">Filter aktif:</span>
                        @if(filled($keyword))
                            <a href="{{ request()->fullUrlWithQuery(['keyword' => null]) }}"
                                class="badge rounded-pill bg-light text-dark border px-3 py-2 text-decoration-none shadow-sm">
                                "{{ $keyword }}" <span class="ms-1 fw-bold">&times;</span>
                            </a>
                        @endif
                        @if(filled($selectedMerk))
                            <a href="{{ request()->fullUrlWithQuery(['merk' => null]) }}"
                                class="badge rounded-pill bg-light text-dark border px-3 py-2 text-decoration-none shadow-sm">
                                Merk: {{ $selectedMerk }} <span class="ms-1 fw-bold">&times;</span>
                            </a>
                        @endif
                        @if(filled($selectedJenis))
                            <a href="{{ request()->fullUrlWithQuery(['jenis' => null]) }}"
                                class="badge rounded-pill bg-light text-dark border px-3 py-2 text-decoration-none shadow-sm">
                                Jenis: {{ $selectedJenis }} <span class="ms-1 fw-bold">&times;</span>
                            </a>
                        @endif
                        @if(filled($hargaMin) || filled($hargaMax))
                            <a href="{{ request()->fullUrlWithQuery(['harga_min' => null, 'harga_max' => null]) }}"
                                class="badge rounded-pill bg-light text-dark border px-3 py-2 text-decoration-none shadow-sm">
                                Harga: Rp {{ number_format((int) ($hargaMin ?: 0), 0, ',', '.') }} -
                                {{ filled($hargaMax) ? 'Rp ' . number_format((int) $hargaMax, 0, ',', '.') : 'Maks' }}
                                <span class="ms-1 fw-bold">&times;</span>
                            </a>
                        @endif
                        <a href="{{ route('customer.katalog') }}"
                            class="small text-danger text-decoration-none fw-medium ms-1">Reset Semua</a>
                    </div>
                @endif

                <div class="d-flex justify-content-between align-items-center my-3">
                    <h2 class="fw-bold m-0 fs-5 text-dark">Car Tersedia</h2>
                    <p id="jumlah-mobil" class="text-secondary small mb-1">{{ $jumlahCar }} mobil</p>
                </div>

                <div id="daftar-mobil">
                    @forelse ($cars as $mobil)
                        @php
                            $fotoUtama = data_get($mobil, 'fotoUtama.path_foto');
                            $srcFoto = $fotoUtama ? asset('storage/'.$fotoUtama) : asset('img/foto-mobil-kecil.png');
                        @endphp

                        <article class="card border-0 rounded-4 shadow-sm p-3 mb-3 bg-white item-mobil"
                            data-merk="{{ $mobil->merk ?? '-' }}" data-jenis="{{ $mobil->jenis ?? '-' }}"
                            data-harga="{{ $mobil->harga_sewa ?? 0 }}">
                            <a href="{{ route('customer.detail-mobil', $mobil->car_id) }}"
                                class="text-decoration-none text-dark d-flex align-items-center gap-3">
                                <img src="{{ $srcFoto }}" alt="{{ $mobil->nama_mobil ?? 'Car' }}"
                                    class="rounded-3 object-fit-cover" style="width: 110px; height: 80px;" />
                                <div class="flex-grow-1">
                                    <h3 class="h6 fw-bold mb-1" style="color: #0b1d33;">
                                        {{ $mobil->merk ?? '' }} {{ $mobil->nama_mobil ?? '-' }}</h3>
                                    <p class="text-secondary small mb-2" style="color: #0b1d33;">
                                        {{ $mobil->jenis ?? '-' }} &middot; {{ $mobil->spesifikasi ?? '-' }}</p>
                                    <p class="fw-bold mb-2" style="color: #0b1d33;">Rp
                                        {{ number_format($mobil->harga_sewa ?? 0, 0, ',', '.') }} / hari</p>
                                    <span
                                        class="badge rounded-pill bg-success-subtle text-success fw-medium px-3 py-1">{{ $mobil->status ?? 'Tersedia' }}</span>
                                </div>
                                <img src="{{ asset('img/katalog mobil/panah-kanan-bold.svg') }}" alt="" />
                            </a>
                        </article>
                    @empty
                        <article class="card border-0 rounded-4 shadow-sm p-4 mb-3 bg-white text-center text-muted">
                            <p class="mb-0 small">Belum ada mobil tersedia dengan filter tersebut.</p>
                            <small class="text-muted d-block mt-1">Coba gunakan kata kunci lain atau reset filter.</small>
                        </article>
                    @endforelse
                </div>

            </div>
        </section>
    </main>

    <div style="height: 100px; background-color: aliceblue"></div>

    <nav class="navbar fixed-bottom bg-white border-top py-2 px-3 shadow-sm">
        <div class="container-fluid d-flex justify-content-around align-items-center">
            <a href="{{ route('customer.beranda') }}" aria-label="Menuju beranda"
                class="d-flex flex-column align-items-center text-decoration-none py-1 px-3">
                <img src="{{ asset('img/katalog mobil/rumah-biru.svg') }}" alt="Beranda"
                    style="width: 22px; height: 22px; opacity: 0.6;" />
                <span class="small mt-1 text-muted" style="font-size: 0.7rem;">Beranda</span>
            </a>
            <a href="{{ route('customer.katalog') }}" aria-label="Menuju katalog"
                class="d-flex flex-column align-items-center text-decoration-none py-1 px-3 rounded-pill"
                style="background-color: rgba(255, 107, 53, 0.12);">
                <img src="{{ asset('img/katalog mobil/logo-mobil-oren.svg') }}" alt="Katalog"
                    style="width: 22px; height: 22px;filter: invert(41%) sepia(85%) saturate(1436%) hue-rotate(338deg) brightness(101%) contrast(97%);" />
                <span class="small mt-1 text-muted" style="font-size: 0.7rem;">Katalog</span>
            </a>
            <a href="{{ route('customer.riwayat') }}" aria-label="Menuju riwayat"
                class="d-flex flex-column align-items-center text-decoration-none py-1 px-3">
                <img src="{{ asset('img/katalog mobil/jam.svg') }}" alt="Riwayat"
                    style="width: 22px; height: 22px; opacity: 0.6;" />
                <span class="small mt-1 text-muted" style="font-size: 0.7rem;">Riwayat</span>
            </a>
            <a href="{{ route('customer.profil') }}" aria-label="Menuju profil"
                class="d-flex flex-column align-items-center text-decoration-none py-1 px-3">
                <img src="{{ asset('img/katalog mobil/user.svg') }}" alt="Profil"
                    style="width: 22px; height: 22px; opacity: 0.6;" />
                <span class="small mt-1 text-muted" style="font-size: 0.7rem;">Profil</span>
            </a>
        </div>
    </nav>
@endsection

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script src="{{ asset('js/customer/katalog.js') }}"></script>
@endpush
