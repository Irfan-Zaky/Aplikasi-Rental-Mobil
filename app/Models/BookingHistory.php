<?php

namespace App\Models;

use App\Enums\RentalStatus;
use Database\Factories\BookingHistoryFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property RentalStatus $status
 */
class BookingHistory extends Model
{
    /** @use HasFactory<BookingHistoryFactory> */
    use HasFactory;

    /** @var string */
    protected $table = 'booking_history';

    /** @var string */
    protected $primaryKey = 'history_id';

    /** @var list<string> */
    protected $fillable = [
        'rental_id',
        'sequence_no',
        'status',
        'changed_by',
        'note',
        'changed_at',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'sequence_no' => 'integer',
            'changed_at' => 'datetime',
            'status' => RentalStatus::class,
        ];
    }

    public function rental(): BelongsTo
    {
        return $this->belongsTo(Rental::class, 'rental_id');
    }

    public function changedBy(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'changed_by');
    }
}
