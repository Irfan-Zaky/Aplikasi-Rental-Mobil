<?php

namespace App\Models;

use Database\Factories\CarPhotoFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CarPhoto extends Model
{
    /** @use HasFactory<CarPhotoFactory> */
    use HasFactory;

    /** @var string */
    protected $table = 'car_photo';

    /** @var string */
    protected $primaryKey = 'photo_id';

    /** @var list<string> */
    protected $fillable = [
        'car_id',
        'path_foto',
        'is_utama',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'is_utama' => 'boolean',
        ];
    }

    public function mobil(): BelongsTo
    {
        return $this->belongsTo(Car::class, 'car_id');
    }

    public function car(): BelongsTo
    {
        return $this->mobil();
    }
}
