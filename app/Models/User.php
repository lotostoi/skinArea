<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\UserRole;
use Database\Factories\UserFactory;
use Filament\Models\Contracts\FilamentUser;
use Filament\Models\Contracts\HasName;
use Filament\Panel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable implements FilamentUser, HasName
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens;

    use HasFactory;
    use Notifiable;
    use SoftDeletes;

    protected $fillable = [
        'steam_id',
        'username',
        'avatar_url',
        'trade_url',
        'email',
        'password',
        'role',
        'is_banned',
        'banned_until',
        'ban_reason',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    public function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'role' => UserRole::class,
            'is_banned' => 'boolean',
            'banned_until' => 'datetime',
        ];
    }

    public function getMorphClass(): string
    {
        return 'user';
    }

    public function balances(): HasMany
    {
        return $this->hasMany(Balance::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    public function marketItems(): HasMany
    {
        return $this->hasMany(MarketItem::class, 'seller_id');
    }

    public function dealsAsBuyer(): HasMany
    {
        return $this->hasMany(Deal::class, 'buyer_id');
    }

    public function dealsAsSeller(): HasMany
    {
        return $this->hasMany(Deal::class, 'seller_id');
    }

    public function caseOpenings(): HasMany
    {
        return $this->hasMany(CaseOpening::class);
    }

    public function upgrades(): HasMany
    {
        return $this->hasMany(Upgrade::class);
    }

    public function withdrawalRequests(): HasMany
    {
        return $this->hasMany(WithdrawalRequest::class);
    }

    public function processedWithdrawalRequests(): HasMany
    {
        return $this->hasMany(WithdrawalRequest::class, 'processed_by');
    }

    public function canAccessPanel(Panel $panel): bool
    {
        return $this->role === UserRole::Admin
            && ! $this->is_banned
            && $this->email !== null
            && $this->password !== null;
    }

    public function getFilamentName(): string
    {
        return $this->username;
    }
}
