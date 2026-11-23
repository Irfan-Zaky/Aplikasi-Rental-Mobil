@extends('layouts.customer')

@section('title', 'Syarat & Ketentuan')

@section('content')
    <nav class="navbar bg-white py-3 border-bottom">
        <div class="container-fluid position-relative d-flex justify-content-center align-items-center px-3">
            <a href="{{ route('customer.ketersediaan-mobil').'?'.http_build_query([
                'id' => data_get($draft, 'car_id'),
                'tanggal-mulai' => Carbon\Carbon::parse(data_get($draft, 'start_date'))->toDateString(),
                'tanggal-selesai' => Carbon\Carbon::parse(data_get($draft, 'end_date'))->toDateString(),
            ]) }}" aria-label="Kembali ke Ketersediaan Car"
                class="position-absolute start-0 ms-3 d-flex align-items-center justify-content-center rounded-circle bg-light text-decoration-none text-dark fw-bold fs-3 pb-1"
                style="width: 40px; height: 40px; color: #0b1d33;">&lsaquo;</a>
            <h2 class="fs-3 fw-bold m-0" style="color: #0b1d33;">Syarat &amp; ketentuan</h2>
        </div>
    </nav>

    <main>
        <section>
            <div class="container my-5">
                <div class="row justify-content-center">
                    <div class="col-md-8 col-lg-6">
                        <div class="card border-0 shadow-sm rounded-4 p-2">
                            <div class="card-body">
                                <div class="d-flex align-items-center mb-4">
                                    <div class="bg-primary bg-opacity-10 p-2 rounded-3 me-3 d-flex align-items-center justify-content-center"
                                        style="width: 48px; height: 48px;">
                                        <img src="{{ asset('img/syarat-dan-ketentuan/icon-syarat-sebelum-ketentuan-dan-penyewaan.svg') }}"
                                            alt="Ikon Ketentuan" width="28" height="28" />
                                    </div>

                                    <div>
                                        <h2 class="fs-4 fw-bold text-dark mb-0">Ketentuan Penyewaan</h2>
                                        <p class="text-muted small mb-0">Diperbarui 29 September 2026</p>
                                    </div>
                                </div>

                                <ol class="ps-3 mb-0">
                                    <li class="fw-bold mb-3">
                                        <span class="fw-bold text-dark">Syarat pengemudi</span>
                                        <p class="text-muted small mb-0 mt-1">
                                            Berusia minimal 21 tahun, memiliki SIM A aktif, dan menunjukkan identitas asli
                                            saat serah terima.
                                        </p>
                                    </li>

                                    <li class="fw-bold mb-3">
                                        <span class="fw-bold text-dark">Penggunaan kendaraan</span>
                                        <p class="text-muted small mb-0 mt-1">
                                            Kendaraan hanya digunakan di wilayah yang disepakati dan tidak untuk kegiatan
                                            melanggar hukum.
                                        </p>
                                    </li>

                                    <li class="fw-bold mb-3">
                                        <span class="fw-bold text-dark">Bahan bakar &amp; kebersihan</span>
                                        <p class="text-muted small mb-0 mt-1">
                                            Kendaraan dikembalikan dengan tingkat bahan bakar yang sama dan kondisi interior
                                            tetap bersih.
                                        </p>
                                    </li>

                                    <li class="fw-bold mb-3">
                                        <span class="fw-bold text-dark">Keterlambatan</span>
                                        <p class="text-muted small mb-0 mt-1">
                                            Keterlambatan pengembalian dikenakan biaya sesuai tarif per jam yang berlaku.
                                        </p>
                                    </li>

                                    <li class="fw-bold mb-0">
                                        <span class="fw-bold text-dark">Pembatalan</span>
                                        <p class="text-muted small mb-0 mt-1">
                                            Pembatalan maksimal 24 jam sebelum waktu sewa mendapatkan pengembalian penuh.
                                        </p>
                                    </li>
                                </ol>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <div class="container my-5">
            <div class="row justify-content-center">
                <div class="col-md-6 col-lg-5">
                    <div class="card border-0 shadow-sm rounded-4 p-4">
                        <div class="card-body">
                            <form action="{{ route('customer.syarat.store') }}" method="POST">
                                @csrf
                                <div class="form-check mb-4">
                                    <input class="form-check-input" type="checkbox" id="setuju-syarat"
                                        name="setuju_syarat" value="1" required />
                                    <label class="form-check-label text-dark small" for="setuju-syarat">
                                        Saya telah membaca dan menyetujui syarat &amp; ketentuan penyewaan.
                                    </label>
                                </div>

                                <button type="submit"
                                    class="btn btn-dark w-100 py-2 rounded-3 d-flex align-items-center justify-content-center gap-2"
                                    id="btn-submit" disabled>
                                    <span>Setuju &amp; Lanjut</span>
                                    <img src="{{ asset('img/syarat-dan-ketentuan/setuju-dan-lanjut.svg') }}" alt="Arrow"
                                        width="16" height="16" />
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>
@endsection

@push('scripts')
    <script src="{{ asset('js/customer/syarat-dan-ketentuan.js') }}"></script>
@endpush
