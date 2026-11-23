<?php

namespace App\Http\Controllers;

use App\Models\Car;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\View\View;

class KatalogController extends Controller
{
    public function index(Request $request): View
    {
        $keyword = $request->string('keyword')->trim()->toString();
        $merk = $request->string('merk')->trim()->toString();
        $jenis = $request->string('jenis')->trim()->toString();
        $hargaMin = $request->filled('harga_min') ? (int) $request->input('harga_min') : null;
        $hargaMax = $request->filled('harga_max') ? (int) $request->input('harga_max') : null;

        $cars = Car::query()
            ->with('fotoUtama')
            ->when($keyword !== '', function (Builder $query) use ($keyword): void {
                $query->where(function (Builder $query) use ($keyword): void {
                    $query->where('nama_mobil', 'like', "%{$keyword}%")
                        ->orWhere('merk', 'like', "%{$keyword}%")
                        ->orWhere('spesifikasi', 'like', "%{$keyword}%");
                });
            })
            ->when($merk !== '', function (Builder $query) use ($merk): void {
                $query->where(function (Builder $query) use ($merk): void {
                    $query->where('merk', $merk)
                        ->orWhere('merk', 'like', "%{$merk}%");
                });
            })
            ->when($jenis !== '', fn (Builder $q) => $q->where('jenis', $jenis))
            ->when($request->filled('status'), fn (Builder $q) => $q->where('status', $request->string('status')->toString()))
            ->when($hargaMin !== null, fn (Builder $q) => $q->where('harga_sewa', '>=', $hargaMin))
            ->when($hargaMax !== null, fn (Builder $q) => $q->where('harga_sewa', '<=', $hargaMax))
            ->latest()
            ->get();

        $daftarMerk = Car::query()
            ->whereNotNull('merk')
            ->where('merk', '!=', '')
            ->distinct()
            ->orderBy('merk')
            ->pluck('merk');

        $daftarJenis = collect(['MPV', 'SUV', 'Hatchback', 'Sedan'])
            ->merge(
                Car::query()
                    ->whereNotNull('jenis')
                    ->where('jenis', '!=', '')
                    ->distinct()
                    ->pluck('jenis')
            )
            ->filter()
            ->unique()
            ->values();

        return view('customer.katalog', [
            'cars' => $cars,
            'daftarMerk' => $daftarMerk,
            'daftarJenis' => $daftarJenis,
            'keyword' => $keyword,
            'selectedMerk' => $merk,
            'selectedJenis' => $jenis,
            'hargaMin' => $hargaMin,
            'hargaMax' => $hargaMax,
            'isFilterAktif' => $keyword !== '' || $merk !== '' || $jenis !== '' || $hargaMin !== null || $hargaMax !== null,
        ]);
    }
}
