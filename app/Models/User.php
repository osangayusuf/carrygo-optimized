<?php

namespace App\Models;

use Illuminate\Auth\Authenticatable as AuthenticatableTrait;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[Fillable(['msisdn', 'referral_code', 'referred_by_user_id', 'name', 'role', 'email', 'password', 'checkin_streak', 'last_checkin_date', 'spins_balance'])]
#[Table('carrygo_users')]
class User extends Model implements Authenticatable
{
    use AuthenticatableTrait;
    use HasFactory;

    protected $with = ['activePoint'];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
        ];
    }

    public function referredBy(): BelongsTo
    {
        return $this->belongsTo(self::class, 'referred_by_user_id');
    }

    public function referrals(): HasMany
    {
        return $this->hasMany(self::class, 'referred_by_user_id');
    }

    public function activePoint(): HasOne
    {
        return $this->hasOne(ActivePoint::class, 'msisdn', 'msisdn');
    }

    public function rewardWallet(): HasMany
    {
        return $this->hasMany(RewardWallet::class, 'msisdn', 'msisdn');
    }

    public function userAchievements(): HasMany
    {
        return $this->hasMany(UserAchievement::class, 'msisdn', 'msisdn');
    }
}
