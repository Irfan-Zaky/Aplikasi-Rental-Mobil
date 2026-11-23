<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\AccountFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'email', 'password', 'role', 'no_hp', 'username', 'photo_profil'])]
#[Hidden(['password', 'remember_token'])]
class Account extends Authenticatable
{
    /** @use HasFactory<AccountFactory> */
    use HasFactory, Notifiable;

    /** @var string */
    protected $table = 'account';

    /** @var string */
    protected $primaryKey = 'account_id';

    /**
     * Determine whether the user has admin access.
     */
    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    /**
     * Determine whether the user has customer access.
     */
    public function isCustomer(): bool
    {
        return $this->role === 'user';
    }

    public function rentals(): HasMany
    {
        return $this->hasMany(Rental::class, 'account_id');
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }
}
