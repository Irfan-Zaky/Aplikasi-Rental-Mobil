<?php

namespace App\Http\Controllers;

use App\Enums\CarStatus;
use App\Enums\RentalStatus;
use App\Http\Requests\StoreCarRequest;
use App\Http\Requests\UpdateCarRequest;
use App\Models\CarPhoto;
use App\Models\Car;
use App\Models\Rental;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;

class CarController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): View
    {
        $keyword = $request->string('keyword')->trim()->toString();

        $cars = Car::query()
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

        return view('data-mobil', ['cars' => $cars]);
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
    public function store(StoreCarRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        return DB::transaction(function () use ($validated): RedirectResponse {
            $mobil = Car::create([
                'kode_mobil' => $this->generateKodeCar(),
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
                ->with('success', 'Car berhasil disimpan.');
        });
    }

    /**
     * Display the specified resource.
     */
    public function show(Car $mobil): View
    {
        return view('detail-mobil', [
            'mobil' => $mobil,
            'fotos' => $this->dataFoto($mobil),
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Car $mobil): View
    {
        return view('edit-mobil', [
            'mobil' => $mobil,
            'fotos' => $this->dataFoto($mobil),
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateCarRequest $request, Car $mobil): RedirectResponse
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
            $adaPerubahanFoto = array_key_exists('foto_lama', $validated)
                || array_key_exists('foto_utama_lama', $validated)
                || array_key_exists('fotos', $validated);

            if ($adaPerubahanFoto) {
                $fotoUtamaSebelumnyaId = $mobil->fotoUtama?->photo_id;
                $fotoLamaDiminta = array_key_exists('foto_lama', $validated);
                $fotoLamaIds = collect($validated['foto_lama'] ?? [])
                    ->filter(fn (mixed $id): bool => filled($id))
                    ->map(fn (mixed $id): int => (int) $id)
                    ->all();

                if ($fotoLamaDiminta) {
                    $mobil->fotos()->whereNotIn('photo_id', $fotoLamaIds)->delete();
                }

                $fotoLama = $fotoLamaDiminta
                    ? $mobil->fotos()->whereIn('photo_id', $fotoLamaIds)->get()
                    : $mobil->fotos()->get();

                $fotoUtamaLamaId = isset($validated['foto_utama_lama']) ? (int) $validated['foto_utama_lama'] : null;
                $utamaLamaDipilih = $fotoUtamaLamaId !== null && $fotoLama->contains('photo_id', $fotoUtamaLamaId);
                $indeksUtamaBaru = $utamaLamaDipilih || $fotoBaru === []
                    ? -1
                    : $this->indeksFotoUtama($validated['foto_utama'] ?? null, count($fotoBaru));

                $fotoLama->each(function (CarPhoto $foto) use ($fotoUtamaLamaId, $utamaLamaDipilih): void {
                    $foto->update(['is_utama' => $utamaLamaDipilih && $foto->photo_id === $fotoUtamaLamaId]);
                });

                $this->simpanFoto($mobil, $fotoBaru, $indeksUtamaBaru);

                $adaUtamaBaru = $fotoBaru !== [] && $indeksUtamaBaru >= 0;

                if (! $utamaLamaDipilih && ! $adaUtamaBaru && $fotoLama->isNotEmpty()) {
                    $fotoUtamaFallback = $fotoLama->firstWhere('photo_id', $fotoUtamaSebelumnyaId) ?? $fotoLama->first();

                    $fotoUtamaFallback->update(['is_utama' => true]);
                }
            }

            return redirect()->route('admin.data-mobil.index')
                ->with('success', 'Perubahan mobil berhasil disimpan.');
        });
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Car $mobil): RedirectResponse
    {
        return DB::transaction(function () use ($mobil): RedirectResponse {
            if ($this->punyaRentalPemblokir($mobil)) {
                $mobil->update(['status' => CarStatus::Nonaktif->value]);

                return redirect()->route('admin.data-mobil.index')
                    ->with('success', 'Car dinonaktifkan karena masih memiliki transaksi aktif.');
            }

            $mobil->delete();

            return redirect()->route('admin.data-mobil.index')
                ->with('success', 'Car berhasil dihapus.');
        });
    }

    /**
     * Rental yang masih memblokir tanggal mobil, sesuai aturan 3.1.
     */
    private function punyaRentalPemblokir(Car $mobil): bool
    {
        return Rental::query()
            ->where('car_id', $mobil->car_id)
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

    /**
     * Generate a unique rental car code.
     */
    private function generateKodeCar(): string
    {
        do {
            $kode = 'MOB-'.now()->format('ymd').'-'.Str::upper(Str::random(4));
        } while (Car::where('kode_mobil', $kode)->exists());

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
    private function simpanFoto(Car $mobil, array $fotos, int $indeksUtama): void
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
    private function dataFoto(Car $mobil): array
    {
        return $mobil->fotos()
            ->orderByDesc('is_utama')
            ->orderBy('photo_id')
            ->get()
            ->map(fn (CarPhoto $foto): array => [
                'id' => $foto->photo_id,
                'nama' => 'Foto mobil '.$foto->photo_id,
                'src' => Storage::disk('public')->url($foto->path_foto),
                'utama' => $foto->is_utama,
            ])
            ->all();
    }
}
