<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreMobilRequest;
use App\Http\Requests\UpdateMobilRequest;
use App\Models\FotoMobil;
use App\Models\Mobil;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;

class MobilController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): View
    {
        $keyword = $request->string('keyword')->trim()->toString();

        $mobils = Mobil::query()
            ->with('fotoUtama')
            ->when($keyword !== '', function (Builder $query) use ($keyword): void {
                $query->where(function (Builder $query) use ($keyword): void {
                    $query->where('nama_mobil', 'like', "%{$keyword}%")
                        ->orWhere('merk', 'like', "%{$keyword}%")
                        ->orWhere('nomor_polisi', 'like', "%{$keyword}%");
                });
            })
            ->when($request->filled('merk'), fn (Builder $query): Builder => $query->where('merk', $request->string('merk')->toString()))
            ->when($request->filled('jenis'), fn (Builder $query): Builder => $query->where('jenis', $request->string('jenis')->toString()))
            ->when($request->filled('status'), fn (Builder $query): Builder => $query->where('status', $request->string('status')->toString()))
            ->latest()
            ->orderByDesc('id')
            ->get();

        return view('data-mobil', ['mobils' => $mobils]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): View
    {
        return view('tambah_mobil');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreMobilRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        return DB::transaction(function () use ($validated): RedirectResponse {
            $mobil = Mobil::create([
                'kode_mobil' => $this->generateKodeMobil(),
                'merk' => $validated['merk'],
                'nama_mobil' => $validated['nama'],
                'jenis' => $validated['jenis'],
                'status' => $validated['status'],
                'harga_sewa' => $validated['harga'],
                'nomor_polisi' => $validated['plat'],
                'spesifikasi' => $validated['spesifikasi'],
            ]);

            $this->simpanFoto(
                $mobil,
                $validated['fotos'],
                $this->indeksFotoUtama($validated['foto_utama'] ?? null, count($validated['fotos'])),
            );

            return redirect()->route('admin.data-mobil.index')
                ->with('success', 'Mobil berhasil disimpan.');
        });
    }

    /**
     * Display the specified resource.
     */
    public function show(Mobil $mobil): View
    {
        return view('detail-mobil', [
            'mobil' => $mobil,
            'fotos' => $this->dataFoto($mobil),
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Mobil $mobil): View
    {
        return view('edit-mobil', [
            'mobil' => $mobil,
            'fotos' => $this->dataFoto($mobil),
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateMobilRequest $request, Mobil $mobil): RedirectResponse
    {
        $validated = $request->validated();

        return DB::transaction(function () use ($validated, $mobil): RedirectResponse {
            $mobil->update([
                'merk' => $validated['merk'],
                'nama_mobil' => $validated['nama'],
                'jenis' => $validated['jenis'],
                'status' => $validated['status'],
                'harga_sewa' => $validated['harga'],
                'nomor_polisi' => $validated['plat'],
                'spesifikasi' => $validated['spesifikasi'],
            ]);

            $fotoBaru = $validated['fotos'] ?? [];

            if ($fotoBaru !== []) {
                $mobil->fotos()->update(['is_utama' => false]);

                $this->simpanFoto(
                    $mobil,
                    $fotoBaru,
                    $this->indeksFotoUtama($validated['foto_utama'] ?? null, count($fotoBaru)),
                );
            }

            return redirect()->route('admin.data-mobil.index')
                ->with('success', 'Perubahan mobil berhasil disimpan.');
        });
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Mobil $mobil): RedirectResponse
    {
        return DB::transaction(function () use ($mobil): RedirectResponse {
            $mobil->fotos->each(function (FotoMobil $foto): void {
                Storage::disk('public')->delete($foto->path_foto);
            });

            $mobil->delete();

            return redirect()->route('admin.data-mobil.index')
                ->with('success', 'Mobil berhasil dihapus.');
        });
    }

    /**
     * Generate a unique rental car code.
     */
    private function generateKodeMobil(): string
    {
        do {
            $kode = 'MOB-'.now()->format('ymd').'-'.Str::upper(Str::random(4));
        } while (Mobil::where('kode_mobil', $kode)->exists());

        return $kode;
    }

    /**
     * Normalize the selected photo index to a valid array offset.
     */
    private function indeksFotoUtama(mixed $indeks, int $jumlahFoto): int
    {
        if ($jumlahFoto === 0) {
            return 0;
        }

        return min(max(is_numeric($indeks) ? (int) $indeks : 0, 0), $jumlahFoto - 1);
    }

    /**
     * Store uploaded photos and mark the selected one as the main photo.
     *
     * @param  array<int, UploadedFile>  $fotos
     */
    private function simpanFoto(Mobil $mobil, array $fotos, int $indeksUtama): void
    {
        foreach ($fotos as $indeks => $foto) {
            $mobil->fotos()->create([
                'path_foto' => $foto->store('mobil-fotos', 'public'),
                'is_utama' => $indeks === $indeksUtama,
            ]);
        }
    }

    /**
     * Prepare photo data for the detail and edit views.
     *
     * @return array<int, array{id: int, nama: string, src: string, utama: bool}>
     */
    private function dataFoto(Mobil $mobil): array
    {
        return $mobil->fotos()
            ->orderByDesc('is_utama')
            ->orderBy('id')
            ->get()
            ->map(fn (FotoMobil $foto): array => [
                'id' => $foto->id,
                'nama' => 'Foto mobil '.$foto->id,
                'src' => Storage::disk('public')->url($foto->path_foto),
                'utama' => $foto->is_utama,
            ])
            ->all();
    }
}
