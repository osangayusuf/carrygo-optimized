<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['msisdn', 'total_points', 'bidid'])]
#[Table('carrygo_bid_winners')]
class BidWinner extends Model
{
    public const UPDATED_AT = null;

    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
        ];
    }

    public function bid(): BelongsTo
    {
        return $this->belongsTo(Bid::class, 'bidid');
    }
}
