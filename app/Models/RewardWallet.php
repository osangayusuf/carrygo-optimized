<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Concerns\HasTimestamps;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['msisdn', 'source', 'description', 'points', 'claimed_at'])]
#[Table('carrygo_reward_wallet')]
class RewardWallet extends Model
{
    /** @use HasTimestamps */
    public const UPDATED_AT = null;

    protected function casts(): array
    {
        return [
            'claimed_at' => 'datetime',
            'created_at' => 'datetime',
        ];
    }

    public function isClaimed(): bool
    {
        return $this->claimed_at !== null;
    }
}
