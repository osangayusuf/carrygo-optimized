<?php

namespace App\Services;

use App\Enums\BidStatus;
use App\Models\Bid;
use App\Models\BidActive;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;

class BidExpiryService
{
    public function activeRecord(Bid $bid): ?BidActive
    {
        if ($bid->relationLoaded('bidActive') && $bid->bidActive !== null) {
            return $bid->bidActive;
        }

        return BidActive::query()
            ->where('bidid', $bid->id)
            ->where('status', 0)
            ->orderByDesc('id')
            ->first();
    }

    public function endsAt(Bid $bid): ?CarbonInterface
    {
        $bidActive = $this->activeRecord($bid);

        if ($bidActive === null || $bid->status !== BidStatus::Live) {
            return null;
        }

        return Carbon::instance($bidActive->created_at)->addHours((int) $bid->open_date);
    }

    public function finalWindowStart(Bid $bid): ?CarbonInterface
    {
        $endsAt = $this->endsAt($bid);

        if ($endsAt === null) {
            return null;
        }

        $hours = (int) config('bidding.event_final_window_hours', 24);

        return $endsAt->copy()->subHours($hours);
    }

    public function isInFinalWindow(Bid $bid, ?CarbonInterface $at = null): bool
    {
        $at ??= now();

        $windowStart = $this->finalWindowStart($bid);
        $endsAt = $this->endsAt($bid);

        if ($windowStart === null || $endsAt === null) {
            return false;
        }

        return $at->gte($windowStart) && $at->lt($endsAt);
    }

    public function isEventBid(Bid $bid): bool
    {
        return (bool) $bid->event_special;
    }
}
