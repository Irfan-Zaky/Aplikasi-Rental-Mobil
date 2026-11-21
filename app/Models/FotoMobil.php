<?php

namespace App\Models;

use Database\Factories\FotoMobilFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FotoMobil extends Model
{
    /** @use HasFactory<FotoMobilFactory> */
    use HasFactory;

    /** @var string */
    protected $table = 'mobil_fotos';

    /** @var list<string> */
    protected $fillable = [
        'mobil_id',
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
        return $this->belongsTo(Mobil::class);
    }
}
