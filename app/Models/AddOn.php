<?php

namespace App\Models;

use App\Enums\HandoverMethod;
use Database\Factories\AddOnFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property HandoverMethod|null $handover_method
 */
class AddOn extends Model
{
    /** @use HasFactory<AddOnFactory> */
    use HasFactory;

    /** @var string */
    protected $table = 'add_on';

    /** @var string */
    protected $primaryKey = 'rental_id';

    /** @var bool */
    public $incrementing = false;

    /** @var string */
    protected $keyType = 'int';

    /** @var list<string> */
    protected $fillable = [
        'rental_id',
        'handover_method',
        'pickup_point',
        'driver_fee',
        'driver_name',
        'driver_phone',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'driver_fee' => 'integer',
            'handover_method' => HandoverMethod::class,
        ];
    }

    public function rental(): BelongsTo
    {
        return $this->belongsTo(Rental::class, 'rental_id');
    }
}
