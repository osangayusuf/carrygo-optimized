<?php

namespace App\Rules;

use App\Models\Bid;
use App\Models\BidEntry;
use App\Services\BidExpiryService;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class MaxEventBidPointsInFinalWindow implements ValidationRule
{
    public function __construct(
        private readonly Bid $bid,
        private readonly string $msisdn,
        private readonly ?BidExpiryService $bidExpiryService = null,
    ) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $points = (int) $value;
        $service = $this->bidExpiryService ?? app(BidExpiryService::class);

        if (! $service->isEventBid($this->bid) || ! $service->isInFinalWindow($this->bid)) {
            return;
        }

        $windowStart = $service->finalWindowStart($this->bid);
        $endsAt = $service->endsAt($this->bid);

        if ($windowStart === null || $endsAt === null) {
            return;
        }

        $spentInWindow = (int) BidEntry::query()
            ->where('bidid', $this->bid->id)
            ->where('msisdn', $this->msisdn)
            ->where('created_at', '>=', $windowStart)
            ->where('created_at', '<', $endsAt)
            ->sum('points');

        $maxPoints = (int) config('bidding.event_final_window_max_points', 1000);

        if ($spentInWindow + $points > $maxPoints) {
            $remaining = max(0, $maxPoints - $spentInWindow);

            $fail("You cannot bid more than {$maxPoints} points within the last 24 hours before this item closes. You have {$remaining} points remaining in this window.");
        }
    }
}
