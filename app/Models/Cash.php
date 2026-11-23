<?php

namespace App\Models;

use Database\Factories\CashFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Cash extends Model
{
    /** @use HasFactory<CashFactory> */
    use HasFactory;

    /** @var string */
    protected $table = 'cash';

    /** @var string */
    protected $primaryKey = 'payment_id';

    /** @var bool */
    public $incrementing = false;

    /** @var string */
    protected $keyType = 'int';

    /** @var list<string> */
    protected $fillable = [
        'payment_id',
        'payment_code',
    ];

    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class, 'payment_id');
    }
}
