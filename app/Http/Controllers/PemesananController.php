<?php

namespace App\Http\Controllers;

use App\Enums\HandoverMethod;
use App\Enums\MobilStatus;
use App\Enums\RentalStatus;
use App\Http\Requests\DataDiriRequest;
use App\Http\Requests\DetailLayananRequest;
use App\Models\Mobil;
use App\Models\Rental;
use App\Models\RentalAddOn;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class PemesananController extends Controller
{
    private const DRAFT_KEY = 'pemesanan_draft';

    /**
     * Display the selected car detail (F002).
     */
    public function detailMobil(Mobil $mobil): View
    {
        $mobil->load(['fotos', 'fotoUtama']);

        return view('customer.detail-mobil', ['mobil' => $mobil]);
    }

    /**
     * Display the date picker (C-07).
     */
    public function pilihTanggal(Request $request): View
    {
        $mobil = Mobil::findOrFail($request->integer('id'));

        if ($this->draft('mobil_id') !== $mobil->id) {
            $this->resetDraft(['mobil_id' => $mobil->id]);
        }

        $tanggalMulai = $request->string('tanggal-mulai')->toString();
        $tanggalSelesai = $request->string('tanggal-selesai')->toString();
        $durasi = null;
        $estimasiBiaya = null;

        if ($tanggalMulai !== '' && $tanggalSelesai !== '') {
            $start = Carbon::parse($tanggalMulai)->startOfDay();
            $end = Carbon::parse($tanggalSelesai)->endOfDay();
            $durasi = $this->hitungDurasi($start, $end);
            $estimasiBiaya = $this->rupiah($durasi * $mobil->harga_sewa);
        }

        return view('customer.pilih-tanggal', [
            'mobil' => $mobil,
            'tanggalMulai' => $tanggalMulai,
            'tanggalSelesai' => $tanggalSelesai,
            'durasi' => $durasi,
            'estimasiBiaya' => $estimasiBiaya,
        ]);
    }

    /**
     * Check availability and keep the booking draft (C-08).
     */
    public function ketersediaanMobil(Request $request): View|RedirectResponse
    {
        $validated = $request->validate([
            'id' => ['required', 'integer', 'exists:mobils,id'],
            'tanggal-mulai' => ['required', 'date_format:Y-m-d', 'after_or_equal:today'],
            'tanggal-selesai' => ['required', 'date_format:Y-m-d', 'after_or_equal:tanggal-mulai'],
        ]);

        $mobil = Mobil::findOrFail($validated['id']);
        $start = Carbon::parse($validated['tanggal-mulai'])->startOfDay();
        $end = Carbon::parse($validated['tanggal-selesai'])->endOfDay();
        $durasi = $this->hitungDurasi($start, $end);

        if (! $this->mobilTersedia($mobil, $start, $end)) {
            return back()->withErrors(['tanggal-mulai' => 'Mobil tidak tersedia pada tanggal yang dipilih.']);
        }

        $this->simpanDraft([
            'mobil_id' => $mobil->id,
            'start_date' => $start->toDateTimeString(),
            'end_date' => $end->toDateTimeString(),
            'duration' => $durasi,
            'price_per_day' => $mobil->harga_sewa,
            'subtotal' => $durasi * $mobil->harga_sewa,
        ]);

        return view('customer.ketersediaan-mobil', [
            'mobil' => $mobil,
            'tanggalMulai' => $start->toDateString(),
            'tanggalSelesai' => $end->toDateString(),
            'durasi' => $durasi.' hari',
            'estimasiBiaya' => $this->rupiah($durasi * $mobil->harga_sewa),
        ]);
    }

    /**
     * Display the terms and conditions (C-09).
     */
    public function syarat(): View|RedirectResponse
    {
        if (! $this->draft('mobil_id')) {
            return redirect()->route('customer.katalog');
        }

        return view('customer.syarat-dan-ketentuan', ['draft' => $this->draft()]);
    }

    /**
     * Record acceptance of the terms (C-09).
     */
    public function syaratStore(Request $request): RedirectResponse
    {
        $request->validate(['setuju_syarat' => ['required', 'accepted']]);

        $this->simpanDraft(['terms_accepted_at' => now()->toDateTimeString()]);

        return redirect()->route('customer.pilihan-layanan');
    }

    /**
     * Display the service choice (C-10).
     */
    public function pilihanLayanan(): View
    {
        return view('customer.pilihan-layanan');
    }

    /**
     * Record the service choice (C-10).
     */
    public function pilihanLayananStore(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'layanan' => ['required', 'in:dengan-sopir,lepas-kunci'],
        ]);

        $this->simpanDraft(['self_driver' => $validated['layanan'] === 'lepas-kunci']);

        return redirect()->route(
            $validated['layanan'] === 'lepas-kunci'
                ? 'customer.detail-layanan.lepas-kunci'
                : 'customer.detail-layanan.sopir'
        );
    }

    /**
     * Display the driver service detail (C-11).
     */
    public function detailLayananSopir(): View|RedirectResponse
    {
        if ($this->draft('self_driver') !== false) {
            return redirect()->route('customer.pilihan-layanan');
        }

        return view('customer.detail-layanan-sopir');
    }

    /**
     * Display the self-drive service detail (C-11).
     */
    public function detailLayananLepasKunci(): View|RedirectResponse
    {
        if ($this->draft('self_driver') !== true) {
            return redirect()->route('customer.pilihan-layanan');
        }

        return view('customer.detail-layanan-lepas-kunci');
    }

    /**
     * Record the service detail and calculated total (C-11).
     */
    public function detailLayananStore(DetailLayananRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $selfDriver = $validated['layanan'] === 'lepas-kunci';
        $handoverMethod = null;
        $pickupPoint = null;

        if ($selfDriver) {
            $handoverMethod = $validated['metode_penerimaan'] === 'diantar'
                ? HandoverMethod::Diantar
                : HandoverMethod::AmbilDiGarasi;
            $pickupPoint = $validated['lokasi_pengantaran'] ?? null;
        } else {
            $pickupPoint = $validated['alamat_penjemputan'];
        }

        $driverFee = $selfDriver ? 0 : (int) config('rental.driver_fee_per_day') * (int) $this->draft('duration');
        $subtotal = (int) $this->draft('subtotal');

        $this->simpanDraft([
            'self_driver' => $selfDriver,
            'handover_method' => $handoverMethod?->value,
            'pickup_point' => $pickupPoint,
            'driver_fee' => $driverFee,
            'total_rental' => $subtotal + $driverFee,
        ]);

        return redirect()->route('customer.data-diri');
    }

    /**
     * Display the personal data form (C-12).
     */
    public function dataDiri(Request $request): View|RedirectResponse
    {
        if (! $this->draft('mobil_id')) {
            return redirect()->route('customer.katalog');
        }

        return view('customer.data-diri', [
            'namaPenyewa' => $this->draft('customer.nama_lengkap') ?? $request->user()->name,
            'nomorHpPenyewa' => $this->draft('customer.nomor_hp') ?? $request->user()->no_hp,
            'selfDriver' => $this->draft('self_driver'),
        ]);
    }

    /**
     * Store the personal data and private documents in the draft (C-12).
     */
    public function dataDiriStore(DataDiriRequest $request): RedirectResponse
    {
        if (! $this->draft('mobil_id')) {
            return redirect()->route('customer.katalog');
        }

        $draftCustomer = $this->draft('customer', []);

        foreach (['ktp', 'selfie'] as $jenis) {
            $kunci = $jenis === 'ktp' ? 'foto_ktp' : 'swafoto';

            if (! empty($draftCustomer[$kunci])) {
                Storage::disk('local')->delete($draftCustomer[$kunci]);
            }
        }

        $validated = $request->validated();

        $this->simpanDraft([
            'customer' => [
                'nama_lengkap' => $validated['nama_lengkap'],
                'nomor_hp' => $validated['nomor_hp'],
                'nik' => Crypt::encryptString($validated['nik']),
                'foto_ktp' => $request->file('foto_ktp')->store('verifikasi/ktp', 'local'),
                'swafoto' => $request->file('swafoto')->store('verifikasi/selfie', 'local'),
            ],
        ]);

        return redirect()->route('customer.ringkasan-pemesanan');
    }

    /**
     * Display the booking summary (C-13).
     */
    public function ringkasan(Request $request): View|RedirectResponse
    {
        if (! $this->draftLengkap()) {
            return redirect()->route('customer.katalog');
        }

        return view('customer.ringkasan-pemesanan', [
            'ringkasan' => $this->ringkasanData(),
            'namaPenyewa' => $this->draft('customer.nama_lengkap'),
            'nomorHpPenyewa' => $this->draft('customer.nomor_hp'),
        ]);
    }

    /**
     * Create the rental atomically from the draft (C-13).
     */
    public function ringkasanStore(Request $request): RedirectResponse
    {
        if (! $this->draftLengkap()) {
            return redirect()->route('customer.katalog');
        }

        $draft = $this->draft();
        $rental = DB::transaction(function () use ($draft, $request): Rental {
            $mobil = Mobil::whereKey($draft['mobil_id'])->lockForUpdate()->firstOrFail();
            $start = Carbon::parse($draft['start_date']);
            $end = Carbon::parse($draft['end_date']);

            if (! $this->mobilTersedia($mobil, $start, $end)) {
                abort(409, 'Mobil tidak tersedia pada tanggal yang dipilih.');
            }

            $rental = Rental::create([
                'trx_code' => $this->kodeTransaksi(),
                'user_id' => $request->user()->id,
                'mobil_id' => $mobil->id,
                'start_date' => $start,
                'end_date' => $end,
                'duration' => $draft['duration'],
                'price_per_day' => $draft['price_per_day'],
                'subtotal' => $draft['subtotal'],
                'total_rental' => $draft['total_rental'],
                'late_fee' => 0,
                'self_driver' => $draft['self_driver'],
                'customer_name' => $draft['customer']['nama_lengkap'],
                'customer_nik' => Crypt::decryptString($draft['customer']['nik']),
                'customer_phone' => $draft['customer']['nomor_hp'],
                'customer_ktp_photo' => $draft['customer']['foto_ktp'],
                'customer_selfie_photo' => $draft['customer']['swafoto'],
                'terms_accepted_at' => $draft['terms_accepted_at'],
                'status' => RentalStatus::MenungguPembayaran,
                'expires_at' => now()->addMinutes((int) config('rental.pending_ttl_minutes')),
            ]);

            RentalAddOn::create([
                'rental_id' => $rental->id,
                'handover_method' => $draft['handover_method'],
                'pickup_point' => $draft['pickup_point'],
                'driver_fee' => $draft['driver_fee'],
            ]);

            $rental->bookingHistories()->create([
                'sequence_no' => 1,
                'status' => RentalStatus::MenungguPembayaran,
                'changed_by' => $request->user()->id,
                'note' => 'Pemesanan dibuat',
                'changed_at' => now(),
            ]);

            return $rental;
        });

        session()->forget(self::DRAFT_KEY);
        session(['pemesanan_terakhir' => $rental->id]);

        return redirect()->route('customer.pilihan-pembayaran');
    }

    /**
     * Display the payment method screen (C-14).
     */
    public function pilihanPembayaran(): View|RedirectResponse
    {
        $rental = Rental::query()
            ->with('mobil.fotoUtama')
            ->where('user_id', auth()->id())
            ->find(session('pemesanan_terakhir'));

        if (! $rental) {
            return redirect()->route('customer.katalog');
        }

        return view('customer.pilihan-pembayaran', ['ringkasan' => $this->ringkasanDariRental($rental)]);
    }

    /**
     * @return array<string, mixed>
     */
    private function draft(?string $key = null, mixed $default = null): mixed
    {
        $draft = session(self::DRAFT_KEY, []);

        if ($key === null) {
            return $draft;
        }

        return data_get($draft, $key, $default);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function simpanDraft(array $data): void
    {
        session([self::DRAFT_KEY => array_replace_recursive($this->draft(), $data)]);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function resetDraft(array $data): void
    {
        session([self::DRAFT_KEY => $data]);
    }

    private function mobilTersedia(Mobil $mobil, Carbon $start, Carbon $end): bool
    {
        if (in_array($mobil->status, [MobilStatus::Perbaikan->value, MobilStatus::Nonaktif->value], true)) {
            return false;
        }

        return ! Rental::query()
            ->where('mobil_id', $mobil->id)
            ->where('start_date', '<=', $end)
            ->where('end_date', '>=', $start)
            ->where(function ($query): void {
                $query->whereIn('status', [
                    RentalStatus::MenungguVerifikasi->value,
                    RentalStatus::PembayaranBerhasil->value,
                    RentalStatus::Berjalan->value,
                    RentalStatus::Terlambat->value,
                ])->orWhere(function ($query): void {
                    $query->where('status', RentalStatus::MenungguPembayaran->value)
                        ->where('expires_at', '>', now());
                });
            })
            ->exists();
    }

    private function draftLengkap(): bool
    {
        return $this->draft('mobil_id') !== null
            && $this->draft('customer.nama_lengkap') !== null
            && $this->draft('total_rental') !== null
            && $this->draft('terms_accepted_at') !== null;
    }

    /**
     * @return array<string, mixed>
     */
    private function ringkasanData(): array
    {
        $mobil = Mobil::findOrFail($this->draft('mobil_id'));
        $durasi = (int) $this->draft('duration');
        $driverFee = (int) $this->draft('driver_fee');

        return [
            'foto' => $mobil->fotoUtama?->path_foto,
            'nama_mobil' => $mobil->merk.' '.$mobil->nama_mobil,
            'harga_mobil' => $this->rupiah($mobil->harga_sewa),
            'tanggal' => Carbon::parse($this->draft('start_date'))->format('d M Y')
                .' - '.Carbon::parse($this->draft('end_date'))->format('d M Y'),
            'durasi' => $durasi.' hari',
            'layanan' => $this->draft('self_driver') ? 'Lepas Kunci' : 'Dengan Sopir',
            'penerimaan' => $this->draft('handover_method') ?? 'Dijemput sopir',
            'lokasi' => $this->draft('pickup_point') ?? '-',
            'biaya_sewa' => $this->rupiah((int) $this->draft('subtotal')),
            'biaya_tambahan' => $this->rupiah($driverFee),
            'total_biaya' => $this->rupiah((int) $this->draft('total_rental')),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function ringkasanDariRental(Rental $rental): array
    {
        return [
            'foto' => $rental->mobil?->fotoUtama?->path_foto,
            'nama_mobil' => $rental->mobil ? $rental->mobil->merk.' '.$rental->mobil->nama_mobil : '-',
            'harga_mobil' => $this->rupiah($rental->price_per_day),
            'tanggal' => $rental->start_date->format('d M Y').' - '.$rental->end_date->format('d M Y'),
            'durasi' => $rental->duration.' hari',
            'layanan' => $rental->self_driver ? 'Lepas Kunci' : 'Dengan Sopir',
            'penerimaan' => $rental->addOn?->handover_method?->value ?? 'Dijemput sopir',
            'lokasi' => $rental->addOn?->pickup_point ?? '-',
            'biaya_sewa' => $this->rupiah($rental->subtotal),
            'biaya_tambahan' => $this->rupiah($rental->addOn?->driver_fee ?? 0),
            'total_biaya' => $this->rupiah($rental->total_rental),
        ];
    }

    private function rupiah(int $amount): string
    {
        return 'Rp '.number_format($amount, 0, ',', '.');
    }

    /**
     * Jumlah hari kalender inklusif, tanpa pecahan dari waktu end_of_day.
     */
    private function hitungDurasi(Carbon $start, Carbon $end): int
    {
        return (int) $start->copy()->startOfDay()->diffInDays($end->copy()->startOfDay()) + 1;
    }

    private function kodeTransaksi(): string
    {
        do {
            $kode = 'TRX-'.str_pad((string) ((int) Rental::query()->max('id') + 1), 6, '0', STR_PAD_LEFT);
        } while (Rental::where('trx_code', $kode)->exists());

        return $kode;
    }
}
