<?php

namespace App\Http\Controllers;

use App\Enums\RentalStatus;
use App\Http\Requests\CustomerProfileUpdateRequest;
use App\Models\Rental;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class CustomerController extends Controller
{
    /**
     * Display the customer dashboard.
     */
    public function beranda(Request $request): View
    {
        $user = $request->user();

        $jumlahAktif = $user->rentals()
            ->whereIn('status', [RentalStatus::PembayaranBerhasil, RentalStatus::Berjalan, RentalStatus::Terlambat])
            ->count();

        $jumlahMenunggu = $user->rentals()
            ->whereIn('status', [RentalStatus::MenungguPembayaran, RentalStatus::MenungguVerifikasi])
            ->count();

        $jumlahSelesai = $user->rentals()
            ->where('status', RentalStatus::Selesai)
            ->count();

        $pesananTerakhir = $user->rentals()
            ->with(['mobil.fotoUtama'])
            ->latest()
            ->first();

        return view('customer.beranda', [
            'namaCustomer' => $user->name,
            'fotoProfil' => $user->photo_profil,
            'jumlahAktif' => $jumlahAktif,
            'jumlahMenunggu' => $jumlahMenunggu,
            'jumlahSelesai' => $jumlahSelesai,
            'pesananTerakhir' => $pesananTerakhir,
        ]);
    }

    /**
     * Display the customer's own rental history.
     */
    public function riwayat(Request $request): View
    {
        $tab = $request->query('tab', 'semua');

        $query = $request->user()
            ->rentals()
            ->with(['mobil.fotoUtama', 'payments'])
            ->latest();

        // Filter tab: Semua, Aktif, Selesai
        if ($tab === 'aktif') {
            $query->whereIn('status', [RentalStatus::PembayaranBerhasil, RentalStatus::Berjalan, RentalStatus::Terlambat]);
        } elseif ($tab === 'selesai') {
            $query->where('status', RentalStatus::Selesai);
        }

        // Pencarian jika ada
        if ($keyword = $request->query('keyword')) {
            $query->where(function ($q) use ($keyword): void {
                $q->where('trx_code', 'like', "%{$keyword}%")
                    ->orWhere('booking_code', 'like', "%{$keyword}%")
                    ->orWhereHas('mobil', function ($m) use ($keyword): void {
                        $m->where('nama_mobil', 'like', "%{$keyword}%")
                            ->orWhere('merk', 'like', "%{$keyword}%");
                    });
            });
        }

        $rentals = $query->get();

        $riwayat = $rentals->map(function (Rental $rental) {
            $total = $rental->total_rental + ($rental->late_fee ?? 0);
            $fotoPath = $rental->mobil?->fotoUtama?->path_foto;

            return (object) [
                'id' => $rental->rental_id,
                'kode' => $rental->booking_code ?? $rental->trx_code,
                'nama_mobil' => $rental->mobil ? ($rental->mobil->merk.' '.$rental->mobil->nama_mobil) : 'Car',
                'foto_mobil' => $fotoPath ? asset('storage/'.$fotoPath) : asset('img/foto-mobil-kecil.png'),
                'tgl_sewa' => $rental->start_date->format('d M').' - '.$rental->end_date->format('d M Y'),
                'total_biaya' => 'Rp '.number_format($total, 0, ',', '.'),
                'status' => $rental->status->value,
            ];
        });

        return view('customer.riwayat', [
            'riwayat' => $riwayat,
            'tab' => $tab,
        ]);
    }

    /**
     * Display single order detail for customer.
     */
    public function detailPesanan(Request $request): View
    {
        $id = $request->query('id');

        $rental = $request->user()
            ->rentals()
            ->with(['mobil.fotoUtama', 'addOn', 'payments'])
            ->findOrFail($id);

        $tahap = match ($rental->status) {
            RentalStatus::MenungguPembayaran,
            RentalStatus::MenungguVerifikasi => 1,
            RentalStatus::PembayaranBerhasil,
            RentalStatus::Berjalan,
            RentalStatus::Terlambat => 2,
            RentalStatus::Selesai => 3,
            RentalStatus::Dibatalkan => 1,
        };

        $total = $rental->total_rental + ($rental->late_fee ?? 0);

        $pesanan = (object) [
            'id' => $rental->rental_id,
            'tahap' => $tahap,
            'kode' => $rental->booking_code ?? $rental->trx_code,
            'nama_mobil' => $rental->mobil ? ($rental->mobil->merk.' '.$rental->mobil->nama_mobil) : '-',
            'tgl_sewa' => $rental->start_date->format('d M').' - '.$rental->end_date->format('d M Y'),
            'layanan' => $rental->self_driver ? 'Lepas Kunci' : 'Dengan Sopir',
            'penerimaan' => $rental->addOn?->handover_method?->value === 'Diantar'
                ? 'Diantar ('.($rental->addOn->pickup_point ?? '-').')'
                : 'Ambil di garasi',
            'nama_customer' => $rental->customer_name,
            'nomor_hp' => $rental->customer_phone,
            'total_biaya' => 'Rp '.number_format($total, 0, ',', '.'),
            'status' => $rental->status->value,
        ];

        return view('customer.detail-pesanan', [
            'pesanan' => $pesanan,
            'rental' => $rental,
        ]);
    }

    /**
     * Display the customer's profile.
     */
    public function profil(Request $request): View
    {
        $user = $request->user();

        return view('customer.profil', [
            'namaCustomer' => $user->name,
            'emailCustomer' => $user->email,
            'nomorHpCustomer' => $user->no_hp,
            'fotoProfil' => $user->photo_profil,
        ]);
    }

    /**
     * Display the customer profile edit form.
     */
    public function editProfil(Request $request): View
    {
        $user = $request->user();

        return view('customer.edit-profil', [
            'namaCustomer' => $user->name,
            'emailCustomer' => $user->email,
            'nomorHpCustomer' => $user->no_hp,
            'fotoProfil' => $user->photo_profil,
        ]);
    }

    /**
     * Update the customer's profile information.
     */
    public function updateProfil(CustomerProfileUpdateRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $user = $request->user();

        $user->fill([
            'name' => $data['name'],
            'email' => $data['email'],
            'no_hp' => $data['no_hp'],
        ]);

        if (filled($data['password'] ?? null)) {
            $user->password = $data['password'];
        }

        if ($request->boolean('hapus_foto_profil')) {
            if ($user->photo_profil) {
                Storage::disk('public')->delete($user->photo_profil);
            }

            $user->photo_profil = null;
        } elseif ($request->hasFile('photo_profil')) {
            if ($user->photo_profil) {
                Storage::disk('public')->delete($user->photo_profil);
            }

            $user->photo_profil = $request->file('photo_profil')->store('profil', 'public');
        }

        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }

        $user->save();

        return redirect()->route('customer.profil')->with('status', 'profil-updated');
    }
}
