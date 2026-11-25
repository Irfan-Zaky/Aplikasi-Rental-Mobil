<?php

namespace App\Http\Controllers;

use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\PaymentType;
use App\Enums\RentalStatus;
use App\Models\Car;
use App\Models\Payment;
use App\Models\Rental;
use App\Services\Payment\LateFeeCalculator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class AdminController extends Controller
{
    public function __construct(private LateFeeCalculator $lateFeeCalculator) {}

    /**
     * Display the admin dashboard.
     */
    public function index(): View
    {
        $menungguQuery = $this->rentalMenungguKonfirmasi();

        return view('dashboard', [
            'title' => 'home',
            'totalCar' => Car::count(),
            'mobilTersedia' => Car::where('status', 'Tersedia')->count(),
            'mobilDisewa' => Car::where('status', 'Disewa')->count(),
            'totalPesanan' => Rental::count(),
            'pesananMenunggu' => (clone $menungguQuery)->count(),
            'daftarMenunggu' => $menungguQuery->latest()->limit(5)->get()
                ->map(fn (Rental $rental): object => (object) [
                    'id' => $rental->rental_id,
                    'kode' => $rental->booking_code ?? $rental->trx_code,
                    'customer' => $rental->customer_name,
                    'mobil' => $rental->mobil ? $rental->mobil->merk.' '.$rental->mobil->nama_mobil : '-',
                    'metode' => $rental->payments->firstWhere('type', PaymentType::Sewa)?->method?->value ?? '-',
                    'total' => 'Rp '.number_format($rental->total_rental, 0, ',', '.'),
                    'status' => $rental->status->value,
                ]),
        ]);
    }

    /**
     * Display unfinished transactions with filters and search.
     */
    public function transaksi(Request $request): View
    {
        $filter = $request->query('filter');

        $query = $filter === 'konfirmasi'
            ? $this->rentalMenungguKonfirmasi()->latest()
            : Rental::query()
                ->with(['mobil', 'payments.qris', 'payments.cash', 'user'])
                ->whereNotIn('status', [RentalStatus::Selesai, RentalStatus::Dibatalkan])
                ->latest();

        // 1. Filter Tab / Tombol: QRIS, Tunai, Penyelesaian
        if ($filter === 'qris') {
            $query->whereHas('payments', function ($p): void {
                $p->where('method', PaymentMethod::QRIS);
            });
        } elseif ($filter === 'tunai') {
            $query->whereHas('payments', function ($p): void {
                $p->where('method', PaymentMethod::Tunai);
            });
        } elseif ($filter === 'penyelesaian') {
            // Unit sudah di customer atau siap dikembalikan
            $query->whereIn('status', [RentalStatus::Berjalan, RentalStatus::Terlambat]);
        }

        // 2. Pencarian kata kunci
        if ($keyword = $request->query('keyword')) {
            $query->where(function ($q) use ($keyword): void {
                $q->where('trx_code', 'like', "%{$keyword}%")
                    ->orWhere('booking_code', 'like', "%{$keyword}%")
                    ->orWhere('customer_name', 'like', "%{$keyword}%")
                    ->orWhere('customer_phone', 'like', "%{$keyword}%")
                    ->orWhereHas('mobil', function ($m) use ($keyword): void {
                        $m->where('nama_mobil', 'like', "%{$keyword}%")
                            ->orWhere('merk', 'like', "%{$keyword}%")
                            ->orWhere('nomor_polisi', 'like', "%{$keyword}%");
                    })
                    ->orWhereHas('payments.cash', function ($cash) use ($keyword): void {
                        $cash->where('payment_code', 'like', "%{$keyword}%");
                    });
            });
        }

        $rentals = $query->get();

        $transaksis = $rentals->map(function (Rental $rental) {
            return (object) [
                'id' => $rental->rental_id,
                'kode' => $rental->booking_code ?? $rental->trx_code,
                'nama_customer' => $rental->customer_name,
                'nama_mobil' => $rental->mobil ? ($rental->mobil->merk.' '.$rental->mobil->nama_mobil) : '-',
                'tgl_sewa' => $rental->start_date->format('d M').' - '.$rental->end_date->format('d M Y'),
                'total_biaya' => 'Rp '.number_format($rental->total_rental, 0, ',', '.'),
                'status' => $rental->status->value,
                'metode_pembayaran' => $rental->payments->firstWhere('type', PaymentType::Sewa)?->method?->value ?? '-',
            ];
        });

        $statistik = [
            'semua_pesanan' => Rental::whereNotIn('status', [RentalStatus::Selesai, RentalStatus::Dibatalkan])->count(),
            'menunggu_konfirmasi' => (clone $this->rentalMenungguKonfirmasi())->count(),
            'sedang_berjalan' => Rental::whereIn('status', [RentalStatus::Berjalan, RentalStatus::Terlambat])->count(),
            'selesai' => Rental::where('status', RentalStatus::Selesai)->count(),
        ];

        return view('transaksi', [
            'transaksis' => $transaksis,
            'statistik' => $statistik,
            'activeFilter' => $filter,
        ]);
    }

    /**
     * Display detail for an unfinished transaction.
     */
    public function showTransaksi(Rental $rental): View
    {
        $rental->load(['mobil', 'addOn', 'payments.qris', 'payments.cash', 'bookingHistories']);

        $paymentSewa = $rental->payments->firstWhere('type', PaymentType::Sewa) ?? $rental->payments->first();
        $initials = collect(explode(' ', $rental->customer_name))
            ->map(fn ($segment) => mb_substr($segment, 0, 1))
            ->take(2)
            ->join('');

        $transaksiObj = (object) [
            'id' => $rental->rental_id,
            'inisial' => strtoupper($initials ?: 'CU'),
            'nama_customer' => $rental->customer_name,
            'no_hp' => $rental->customer_phone,
            'nama_mobil' => $rental->mobil ? ($rental->mobil->merk.' '.$rental->mobil->nama_mobil) : '-',
            'tgl_sewa' => $rental->start_date->format('d M').' - '.$rental->end_date->format('d M Y'),
            'durasi' => $rental->duration.' hari',
            'layanan' => $rental->self_driver ? 'Lepas Kunci' : 'Dengan Sopir',
            'penerimaan' => $rental->addOn?->handover_method?->value === 'Diantar'
                ? 'Diantar ('.($rental->addOn->pickup_point ?? '-').')'
                : 'Ambil di garasi',
            'status' => $rental->status->value,
            'kode_booking' => $rental->booking_code ?? '-',
            'metode_pembayaran' => $paymentSewa?->method?->value ?? 'Tunai',
            'kode_pembayaran' => $paymentSewa?->cash?->payment_code ?? ($paymentSewa?->qris?->qr_ref ?? '-'),
            'harga_per_hari' => 'Rp '.number_format($rental->price_per_day, 0, ',', '.'),
            'durasi_pembayaran' => $rental->duration.' hari',
            'subtotal' => 'Rp '.number_format($rental->subtotal, 0, ',', '.'),
            'biaya_antar' => 'Rp '.number_format($rental->addOn?->driver_fee ?? 0, 0, ',', '.'),
            'total_biaya' => 'Rp '.number_format($rental->total_rental, 0, ',', '.'),
            'status_pembayaran' => $paymentSewa?->status?->value ?? 'Menunggu',
        ];

        $terlambat = now()->greaterThan($rental->end_date);

        $bisaDikonfirmasi = match ($rental->status) {
            RentalStatus::MenungguVerifikasi => $paymentSewa?->status === PaymentStatus::MenungguKonfirmasi,
            RentalStatus::MenungguPembayaran => $paymentSewa?->method === PaymentMethod::Tunai
                && $paymentSewa?->status === PaymentStatus::MenungguPembayaran
                && ($paymentSewa->expires_at === null || $paymentSewa->expires_at->greaterThan(now())),
            default => false,
        };

        return view('detail-transaksi', [
            'transaksi' => $transaksiObj,
            'rental' => $rental,
            'bisaDikonfirmasi' => $bisaDikonfirmasi,
            'bisaDiselesaikan' => in_array($rental->status, [RentalStatus::Berjalan, RentalStatus::Terlambat], true),
            'bisaDibatalkan' => in_array($rental->status, [
                RentalStatus::MenungguPembayaran,
                RentalStatus::MenungguVerifikasi,
                RentalStatus::PembayaranBerhasil,
            ], true),
            'terlambat' => $terlambat,
            'lamaTerlambat' => max(1, (int) $rental->end_date->diffInDays(now())),
            'dendaOtomatis' => $terlambat
                ? $this->lateFeeCalculator->hitung($rental->end_date, $rental->price_per_day)
                : 0,
        ]);
    }

    /**
     * Stream a private verification document for an admin (F006).
     */
    public function dokumen(Rental $rental, string $jenis): BinaryFileResponse
    {
        $path = match ($jenis) {
            'ktp' => $rental->customer_ktp_photo,
            'sim' => $rental->customer_sim_photo,
            'selfie' => $rental->customer_selfie_photo,
            default => null,
        };

        $disk = Storage::disk('local');

        abort_unless(filled($path) && $disk->exists($path), 404);

        return response()->file($disk->path($path), [
            'Content-Type' => $disk->mimeType($path) ?: 'application/octet-stream',
        ]);
    }

    /**
     * Confirm a verified payment and issue booking/invoice codes (F006).
     */
    public function konfirmasiPembayaran(Request $request, Rental $rental): RedirectResponse
    {
        $admin = $request->user();

        DB::transaction(function () use ($rental, $admin): void {
            $rentalTerkunci = Rental::whereKey($rental->rental_id)->lockForUpdate()->firstOrFail();

            $queryPembayaran = $rentalTerkunci->payments()
                ->where('type', PaymentType::Sewa)
                ->lockForUpdate();

            if ($rentalTerkunci->status === RentalStatus::MenungguVerifikasi) {
                $queryPembayaran->where('status', PaymentStatus::MenungguKonfirmasi);
            } elseif ($rentalTerkunci->status === RentalStatus::MenungguPembayaran) {
                $queryPembayaran->where('method', PaymentMethod::Tunai)
                    ->where('status', PaymentStatus::MenungguPembayaran);
            } else {
                abort(409, 'Pesanan ini tidak menunggu konfirmasi pembayaran.');
            }

            $payment = $queryPembayaran->first();

            abort_unless($payment, 409, 'Pembayaran sewa belum siap dikonfirmasi.');

            if ($rentalTerkunci->status === RentalStatus::MenungguPembayaran) {
                abort_unless(
                    $payment->expires_at === null || $payment->expires_at->greaterThan(now()),
                    409,
                    'Kode tunai sudah kedaluwarsa.'
                );
            }

            $payment->update([
                'status' => PaymentStatus::Berhasil,
                'paid_at' => now(),
                'confirmed_by' => $admin->account_id,
                'confirmed_at' => now(),
            ]);

            $rentalTerkunci->update([
                'booking_code' => $this->kodeBooking(),
                'invoice_number' => $this->nomorInvoice(),
                'invoice_issued_at' => now(),
            ]);

            $rentalTerkunci->transitionTo(
                RentalStatus::PembayaranBerhasil,
                $admin,
                'Pembayaran dikonfirmasi admin'
            );
        });

        return back()->with('success', 'Pembayaran berhasil dikonfirmasi.');
    }

    /**
     * Complete a returned rental, recording a fine when the return is late (F008).
     */
    public function selesaikan(Request $request, Rental $rental): RedirectResponse
    {
        $validated = $request->validate(
            now()->greaterThan($rental->end_date)
                ? ['konfirmasi' => ['required', 'accepted']]
                : [],
            ['konfirmasi.accepted' => 'Konfirmasi denda diperlukan untuk pengembalian terlambat.']
        );

        $admin = $request->user();

        DB::transaction(function () use ($rental, $admin, $validated): void {
            $rentalTerkunci = Rental::whereKey($rental->rental_id)->lockForUpdate()->firstOrFail();

            abort_unless(
                in_array($rentalTerkunci->status, [RentalStatus::Berjalan, RentalStatus::Terlambat], true),
                409,
                'Hanya transaksi Berjalan atau Terlambat yang bisa diselesaikan.'
            );

            $terlambat = now()->greaterThan($rentalTerkunci->end_date);

            if ($terlambat && empty($validated['konfirmasi'])) {
                abort(422, 'Konfirmasi denda diperlukan untuk pengembalian terlambat.');
            }

            $lateFee = $terlambat
                ? $this->lateFeeCalculator->hitung($rentalTerkunci->end_date, $rentalTerkunci->price_per_day)
                : 0;

            $rentalTerkunci->update([
                'returned_at' => now(),
                'late_fee' => $lateFee,
            ]);

            if ($terlambat) {
                Payment::create([
                    'rental_id' => $rentalTerkunci->rental_id,
                    'type' => PaymentType::Denda,
                    'method' => PaymentMethod::Tunai,
                    'amount' => $lateFee,
                    'status' => PaymentStatus::Berhasil,
                    'paid_at' => now(),
                    'confirmed_by' => $admin->account_id,
                    'confirmed_at' => now(),
                ]);
            }

            $rentalTerkunci->transitionTo(
                RentalStatus::Selesai,
                $admin,
                $terlambat ? 'Dikembalikan terlambat' : 'Dikembalikan tepat waktu'
            );
        });

        return back()->with('success', 'Transaksi berhasil diselesaikan.');
    }

    /**
     * Cancel a rental with a mandatory reason (F006).
     */
    public function batalkan(Request $request, Rental $rental): RedirectResponse
    {
        $validated = $request->validate([
            'cancel_reason' => ['required', 'string', 'max:255'],
        ], [
            'cancel_reason.required' => 'Alasan pembatalan wajib diisi.',
        ]);

        $admin = $request->user();

        DB::transaction(function () use ($rental, $admin, $validated): void {
            $rentalTerkunci = Rental::whereKey($rental->rental_id)->lockForUpdate()->firstOrFail();

            abort_unless(in_array($rentalTerkunci->status, [
                RentalStatus::MenungguPembayaran,
                RentalStatus::MenungguVerifikasi,
                RentalStatus::PembayaranBerhasil,
            ], true), 409, 'Pesanan ini tidak bisa dibatalkan.');

            $rentalTerkunci->update([
                'cancel_reason' => $validated['cancel_reason'],
                'cancelled_at' => now(),
            ]);

            $rentalTerkunci->payments()
                ->where('status', '!=', PaymentStatus::Berhasil)
                ->update(['status' => PaymentStatus::Kadaluarsa]);

            $rentalTerkunci->transitionTo(
                RentalStatus::Dibatalkan,
                $admin,
                'Dibatalkan: '.$validated['cancel_reason']
            );
        });

        return back()->with('success', 'Pesanan berhasil dibatalkan.');
    }

    /**
     * Rental yang masih perlu tindakan admin: QRIS sudah dibayar atau Tunai menunggu di garasi.
     */
    private function rentalMenungguKonfirmasi(): Builder
    {
        return Rental::query()
            ->with(['mobil', 'payments'])
            ->where(function ($query): void {
                $query->where(function ($qris): void {
                    $qris->where('status', RentalStatus::MenungguVerifikasi)
                        ->whereHas('payments', function ($payment): void {
                            $payment->where('type', PaymentType::Sewa)
                                ->where('status', PaymentStatus::MenungguKonfirmasi);
                        });
                })->orWhere(function ($tunai): void {
                    $tunai->where('status', RentalStatus::MenungguPembayaran)
                        ->whereHas('payments', function ($payment): void {
                            $payment->where('type', PaymentType::Sewa)
                                ->where('method', PaymentMethod::Tunai)
                                ->where('status', PaymentStatus::MenungguPembayaran)
                                ->where(function ($expiry): void {
                                    $expiry->whereNull('expires_at')
                                        ->orWhere('expires_at', '>', now());
                                });
                        });
                });
            });
    }

    private function kodeBooking(): string
    {
        do {
            $kode = 'BK-'.Str::upper(Str::random(6));
        } while (Rental::where('booking_code', $kode)->exists());

        return $kode;
    }

    private function nomorInvoice(): string
    {
        $prefix = 'INV-'.now()->year.'-';
        $terakhir = Rental::where('invoice_number', 'like', $prefix.'%')->max('invoice_number');
        $urutan = $terakhir ? (int) Str::afterLast($terakhir, '-') + 1 : 1;

        do {
            $nomor = $prefix.str_pad((string) $urutan++, 4, '0', STR_PAD_LEFT);
        } while (Rental::where('invoice_number', $nomor)->exists());

        return $nomor;
    }

    /**
     * Display completed and cancelled transaction history with dynamic filters.
     */
    public function riwayatTransaksi(Request $request): View
    {
        $query = Rental::query()
            ->with(['mobil', 'payments', 'user'])
            ->whereIn('status', [RentalStatus::Selesai, RentalStatus::Dibatalkan])
            ->latest('updated_at');

        // 1. Filter Merk Dinamis (berdasarkan data mobil yang ada di database)
        if ($merk = $request->query('merk')) {
            $query->whereHas('mobil', function ($m) use ($merk): void {
                $m->where('merk', $merk);
            });
        }

        // 2. Filter Jenis Car
        if ($jenis = $request->query('jenis')) {
            $query->whereHas('mobil', function ($m) use ($jenis): void {
                $m->where('jenis', $jenis);
            });
        }

        // 3. Filter Status
        if ($status = $request->query('status')) {
            $query->where('status', $status);
        }

        // 4. Pencarian kata kunci (nama customer, merk, plat nomor, kode)
        if ($keyword = $request->query('keyword')) {
            $query->where(function ($q) use ($keyword): void {
                $q->where('trx_code', 'like', "%{$keyword}%")
                    ->orWhere('booking_code', 'like', "%{$keyword}%")
                    ->orWhere('invoice_number', 'like', "%{$keyword}%")
                    ->orWhere('customer_name', 'like', "%{$keyword}%")
                    ->orWhereHas('mobil', function ($m) use ($keyword): void {
                        $m->where('nama_mobil', 'like', "%{$keyword}%")
                            ->orWhere('merk', 'like', "%{$keyword}%")
                            ->orWhere('nomor_polisi', 'like', "%{$keyword}%");
                    });
            });
        }

        $rentals = $query->get();

        $riwayatTransaksis = $rentals->map(function (Rental $rental) {
            $paymentSewa = $rental->payments->firstWhere('type', PaymentType::Sewa) ?? $rental->payments->first();
            $totalKeseluruhan = $rental->total_rental + ($rental->late_fee ?? 0);

            return (object) [
                'id' => $rental->rental_id,
                'kode' => $rental->booking_code ?? $rental->trx_code,
                'nama_customer' => $rental->customer_name,
                'nama_mobil' => $rental->mobil ? ($rental->mobil->merk.' '.$rental->mobil->nama_mobil) : '-',
                'tgl_sewa' => $rental->start_date->format('d M').' - '.$rental->end_date->format('d M Y'),
                'metode_pembayaran' => $paymentSewa?->method?->value ?? '-',
                'total_biaya' => 'Rp '.number_format($totalKeseluruhan, 0, ',', '.'),
                'status' => $rental->status->value,
            ];
        });

        // Ambil daftar merk secara dinamis dari tabel car untuk foreach select
        $daftarMerk = Car::query()
            ->whereNotNull('merk')
            ->where('merk', '!=', '')
            ->distinct()
            ->orderBy('merk')
            ->pluck('merk');

        // Ambil daftar jenis secara dinamis dari tabel car
        $daftarJenis = Car::query()
            ->whereNotNull('jenis')
            ->where('jenis', '!=', '')
            ->distinct()
            ->orderBy('jenis')
            ->pluck('jenis');

        $totalPendapatanBulanIni = Payment::where('status', PaymentStatus::Berhasil)
            ->whereMonth('paid_at', now()->month)
            ->whereYear('paid_at', now()->year)
            ->sum('amount');

        $formatPendapatan = $totalPendapatanBulanIni >= 1000000
            ? number_format($totalPendapatanBulanIni / 1000000, 1, ',', '.').' jt'
            : number_format($totalPendapatanBulanIni, 0, ',', '.');

        $avgDurasi = Rental::where('status', RentalStatus::Selesai)->avg('duration') ?? 0;

        $statistik = [
            'pendapatan_bulan_ini' => $formatPendapatan,
            'transaksi_selesai' => Rental::where('status', RentalStatus::Selesai)->count(),
            'dibatalkan' => Rental::where('status', RentalStatus::Dibatalkan)->count(),
            'rata_rata_durasi' => number_format((float) $avgDurasi, 1, ',', '.').' hari',
        ];

        return view('riwayat-transaksi', [
            'riwayatTransaksis' => $riwayatTransaksis,
            'daftarMerk' => $daftarMerk,
            'daftarJenis' => $daftarJenis,
            'statistik' => $statistik,
        ]);
    }

    /**
     * Display detail for a completed or cancelled transaction.
     */
    public function showRiwayatTransaksi(Rental $rental): View
    {
        $rental->load(['mobil', 'addOn', 'payments', 'bookingHistories']);

        $paymentSewa = $rental->payments->firstWhere('type', PaymentType::Sewa) ?? $rental->payments->first();
        $totalKeseluruhan = $rental->total_rental + ($rental->late_fee ?? 0);

        $transaksiObj = (object) [
            'id' => $rental->rental_id,
            'kode' => $rental->booking_code ?? $rental->trx_code,
            'nama_customer' => $rental->customer_name,
            'nama_mobil' => $rental->mobil ? ($rental->mobil->merk.' '.$rental->mobil->nama_mobil) : '-',
            'tgl_sewa' => $rental->start_date->format('d M').' - '.$rental->end_date->format('d M Y'),
            'durasi' => $rental->duration.' Hari',
            'layanan' => $rental->self_driver ? 'Lepas Kunci' : 'Dengan Sopir',
            'penerimaan' => $rental->addOn?->handover_method?->value === 'Diantar' ? 'Diantar' : 'Ambil di Garasi',
            'status' => $rental->status->value,
            'metode_pembayaran' => $paymentSewa?->method?->value ?? 'QRIS',
            'harga_per_hari' => 'Rp '.number_format($rental->price_per_day, 0, ',', '.'),
            'subtotal' => 'Rp '.number_format($rental->subtotal, 0, ',', '.'),
            'biaya_tambahan' => 'Rp '.number_format($rental->addOn?->driver_fee ?? 0, 0, ',', '.'),
            'denda' => 'Rp '.number_format($rental->late_fee ?? 0, 0, ',', '.'),
            'total_biaya' => 'Rp '.number_format($totalKeseluruhan, 0, ',', '.'),
            'status_pembayaran' => $paymentSewa?->status?->value ?? ($rental->status === RentalStatus::Dibatalkan ? 'Gagal' : 'Berhasil'),
        ];

        return view('detail-riwayat-transaksi', [
            'transaksi' => $transaksiObj,
            'rental' => $rental,
        ]);
    }
}
