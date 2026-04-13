<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['msisdn', 'rank', 'total_bid_pts', 'week_start', 'bonus_awarded'])]
#[Table('carrygo_leaderboard_snapshots')]
class LeaderboardSnapshot extends Model
{
    public const UPDATED_AT = null;

    protected function casts(): array
    {
        return [
            'week_start' => 'date',
            'created_at' => 'datetime',
            'total_bid_pts' => 'integer',
            'bonus_awarded' => 'integer',
        ];
    }
}
