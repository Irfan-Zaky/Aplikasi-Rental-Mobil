<?php

namespace App\Http\Controllers;

use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\PaymentType;
use App\Enums\RentalStatus;
use App\Models\Car;
use App\Models\Payment;
use App\Models\Rental;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class AdminController extends Controller
{
    /**
     * Display the admin dashboard.
     */
    public function index(): View
    {
        return view('dashboard', [
            'title' => 'home',
            'totalCar' => Car::count(),
            'mobilTersedia' => Car::where('status', 'Tersedia')->count(),
            'mobilDisewa' => Car::where('status', 'Disewa')->count(),
            'totalPesanan' => Rental::count(),
            'pesananMenunggu' => Rental::where('status', RentalStatus::MenungguVerifikasi)->count(),
        ]);
    }

    /**
     * Display unfinished transactions with filters and search.
     */
    public function transaksi(Request $request): View
    {
        $query = Rental::query()
            ->with(['mobil', 'payments.qris', 'payments.cash', 'user'])
            ->whereNotIn('status', [RentalStatus::Selesai, RentalStatus::Dibatalkan])
            ->latest();

        // 1. Filter Tab / Tombol: QRIS, Tunai, Penyelesaian
        $filter = $request->query('filter');
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
        } elseif ($filter === 'konfirmasi') {
            $query->where('status', RentalStatus::MenungguVerifikasi);
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
            'menunggu_konfirmasi' => Rental::where('status', RentalStatus::MenungguVerifikasi)->count(),
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
            'penerimaan' => $rental->addOn?->handover_method === 'Diantar'
                ? 'Diantar ('.($rental->addOn->pickup_point ?? '-').')'
                : 'Ambil di garasi',
            'status' => $rental->status->value,
            'kode_booking' => $rental->booking_code ?? $rental->trx_code,
            'metode_pembayaran' => $paymentSewa?->method?->value ?? 'Tunai',
            'kode_pembayaran' => $paymentSewa?->cash?->payment_code ?? ($paymentSewa?->qris?->qr_ref ?? '-'),
            'harga_per_hari' => 'Rp '.number_format($rental->price_per_day, 0, ',', '.'),
            'durasi_pembayaran' => $rental->duration.' hari',
            'subtotal' => 'Rp '.number_format($rental->subtotal, 0, ',', '.'),
            'biaya_antar' => 'Rp '.number_format($rental->addOn?->driver_fee ?? 0, 0, ',', '.'),
            'total_biaya' => 'Rp '.number_format($rental->total_rental, 0, ',', '.'),
            'status_pembayaran' => $paymentSewa?->status?->value ?? 'Menunggu',
        ];

        return view('detail-transaksi', [
            'transaksi' => $transaksiObj,
            'rental' => $rental,
        ]);
    }

    /**
     * Stream a private verification document for an admin (F006).
     */
    public function dokumen(Rental $rental, string $jenis): BinaryFileResponse
    {
        $path = $jenis === 'ktp'
            ? $rental->customer_ktp_photo
            : $rental->customer_selfie_photo;

        $disk = Storage::disk('local');

        abort_unless(filled($path) && $disk->exists($path), 404);

        return response()->file($disk->path($path), [
            'Content-Type' => $disk->mimeType($path) ?: 'application/octet-stream',
        ]);
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
            'penerimaan' => $rental->addOn?->handover_method === 'Diantar' ? 'Diantar' : 'Ambil di Garasi',
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
