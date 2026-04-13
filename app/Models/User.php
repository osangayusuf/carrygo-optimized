<?php

namespace App\Models;

use Illuminate\Auth\Authenticatable as AuthenticatableTrait;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[Fillable(['msisdn', 'referral_code', 'name', 'role', 'email', 'password', 'checkin_streak', 'last_checkin_date', 'spins_balance'])]
#[Table('carrygo_users')]
#[HasFactory]
class User extends Model implements Authenticatable
{
    use AuthenticatableTrait;

    protected $with = ['activePoint'];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
        ];
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
