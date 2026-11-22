<?php

namespace App\Models;

use Database\Factories\MobilFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Mobil extends Model
{
    /** @use HasFactory<MobilFactory> */
    use HasFactory, SoftDeletes;

    /** @var list<string> */
    protected $fillable = [
        'kode_mobil',
        'merk',
        'nama_mobil',
        'jenis',
        'status',
        'harga_sewa',
        'nomor_polisi',
        'spesifikasi',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'harga_sewa' => 'integer',
        ];
    }

    public function fotos(): HasMany
    {
        return $this->hasMany(FotoMobil::class);
    }

    public function fotoUtama(): HasOne
    {
        return $this->hasOne(FotoMobil::class)
            ->where('is_utama', true)
            ->orderByDesc('id');
    }
}
