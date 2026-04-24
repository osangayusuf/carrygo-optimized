<?php

namespace App\Services;

use App\Enums\BidStatus;
use App\Models\Bid;
use App\Models\BidWinner;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class BidService
{
    public function __construct(private readonly AchievementService $achievementService) {}

    public function closeExpiredBids(): void
    {
        try {
            $liveBids = Bid::query()
                ->with('bidActive', 'bidEntries')
                ->where('status', BidStatus::Live)
                ->whereHas('bidActive', function ($query) {
                    $query->where('status', 0);
                })
                ->get();

            foreach ($liveBids as $bid) {
                $bidActive = $bid->bidActive;
                $now = now();

                $elapsed = $bidActive->created_at->diffInHours($now);

                if ($elapsed < $bid->open_date) {
                    continue;
                }

                $highestBidder = $bid->bidEntries()
                    ->selectRaw('msisdn, SUM(points) as total_points')
                    ->groupBy('msisdn')
                    ->orderByDesc('total_points')
                    ->first();

                DB::transaction(function () use ($bid, $bidActive, $highestBidder) {

                    $cumulativeTotal = $bid->bidEntries()->sum('points');

                    $bidActive->update([
                        'status' => 1,
                        'msisdn' => $highestBidder->msisdn,
                        'points' => $cumulativeTotal,
                    ]);

                    if ($highestBidder) {
                        BidWinner::create([
                            'msisdn' => $highestBidder->msisdn,
                            'bidid' => $bid->id,
                            'total_points' => $highestBidder->total_points,
                        ]);
                    }

                    $bid->update(['status' => BidStatus::Closed]);
                    info('Bid '.$bid->id.' closed successfully at '.now());
                });

                // If a winner was determined, evaluate their bid_won achievement
                if ($highestBidder !== null) {
                    $winner = User::where('msisdn', $highestBidder->msisdn)->first();
                    if ($winner) {
                        $this->achievementService->evaluate($winner, 'bid_won');
                    }
                }
            }
        } catch (\Exception $e) {
            logger()->error('Error closing bid: '.$e->getMessage());
        }
    }
}
