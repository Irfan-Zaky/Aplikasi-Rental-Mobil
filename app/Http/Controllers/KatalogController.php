<?php

namespace App\Http\Controllers;

use App\Models\Mobil;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\View\View;

class KatalogController extends Controller
{
    public function index(Request $request): View
    {
        $keyword = $request->string('keyword')->trim()->toString();

        $mobils = Mobil::query()
            ->with('fotoUtama')
            ->when($keyword !== '', function (Builder $query) use ($keyword): void {
                $query->where(function (Builder $query) use ($keyword): void {
                    $query->where('nama_mobil', 'like', "%{$keyword}%")
                        ->orWhere('merk', 'like', "%{$keyword}%");
                });
            })
            ->when($request->filled('merk'), fn (Builder $q) => $q->where('merk', $request->string('merk')->toString()))
            ->when($request->filled('jenis'), fn (Builder $q) => $q->where('jenis', $request->string('jenis')->toString()))
            ->when($request->filled('status'), fn (Builder $q) => $q->where('status', $request->string('status')->toString()))
            ->when($request->filled('harga_min'), fn (Builder $q) => $q->where('harga_sewa', '>=', (int) $request->input('harga_min')))
            ->when($request->filled('harga_max'), fn (Builder $q) => $q->where('harga_sewa', '<=', (int) $request->input('harga_max')))
            ->latest()
            ->paginate(9)
            ->withQueryString();

        return view('katalog', [
            'mobils'      => $mobils,
            'daftarMerk'  => Mobil::select('merk')->distinct()->orderBy('merk')->pluck('merk'),
            'daftarJenis' => Mobil::select('jenis')->distinct()->orderBy('jenis')->pluck('jenis'),
        ]);
    }
}
