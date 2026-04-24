<?php

namespace App\Models;

use App\Enums\BidStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[Fillable([
    'name',
    'description',
    'specification',
    'category',
    'image',
    'url',
    'price',
    'open_points',
    'open_date',
    'rating',
    'status',
    'event_special',
])]
#[Table('carrygo_bid')]
class Bid extends Model
{
    public const UPDATED_AT = null;

    protected function name(): Attribute
    {
        return Attribute::make(
            get: static function (?string $value): ?string {
                if ($value === null) {
                    return null;
                }

                $value = trim($value);

                // Remove only wrapping quotes (keeps quotes inside the string).
                return preg_replace('/^[\'"\x{2018}\x{2019}\x{201C}\x{201D}]+|[\'"\x{2018}\x{2019}\x{201C}\x{201D}]+$/u', '', $value);
            },
        );
    }

    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
            'rating' => 'decimal:2',
            'event_special' => 'boolean',
            'open_points' => 'integer',
            'status' => BidStatus::class,
        ];
    }

    public function bidEntries(): HasMany
    {
        return $this->hasMany(BidEntry::class, 'bidid');
    }

    public function bidActive(): HasOne
    {
        return $this->hasOne(BidActive::class, 'bidid');
    }

    public function bidActives(): HasMany
    {
        return $this->hasMany(BidActive::class, 'bidid');
    }

    public function bidWinner(): HasOne
    {
        return $this->hasOne(BidWinner::class, 'bidid');
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class, 'bidid')->where('moderation_status', 'approved');
    }
}
