<?php

namespace App\Models;

use Database\Factories\CarFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Car extends Model
{
    /** @use HasFactory<CarFactory> */
    use HasFactory, SoftDeletes;

    /** @var string */
    protected $table = 'car';

    /** @var string */
    protected $primaryKey = 'car_id';

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
        return $this->hasMany(CarPhoto::class, 'car_id');
    }

    public function fotoUtama(): HasOne
    {
        return $this->hasOne(CarPhoto::class, 'car_id')
            ->where('is_utama', true)
            ->orderByDesc('photo_id');
    }
}
