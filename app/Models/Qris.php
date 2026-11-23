<?php

namespace App\Models;

use Database\Factories\QrisFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Qris extends Model
{
    /** @use HasFactory<QrisFactory> */
    use HasFactory;

    /** @var string */
    protected $table = 'qris';

    /** @var string */
    protected $primaryKey = 'payment_id';

    /** @var bool */
    public $incrementing = false;

    /** @var string */
    protected $keyType = 'int';

    /** @var list<string> */
    protected $fillable = [
        'payment_id',
        'qr_ref',
        'qr_code',
        'raw_payload',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'raw_payload' => 'array',
        ];
    }

    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class, 'payment_id');
    }
}
