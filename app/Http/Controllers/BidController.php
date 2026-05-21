<?php

namespace App\Http\Controllers;

use App\Enums\BidStatus;
use App\Http\Requests\PlaceBidRequest;
use App\Models\ActivePoint;
use App\Models\Bid;
use App\Models\BidActive;
use App\Models\BidEntry;
use App\Models\BidWinner;
use App\Models\Point;
use App\Models\User;
use App\Services\AchievementService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;

class BidController extends Controller
{
    public function __construct(private readonly AchievementService $achievementService) {}

    public function store(PlaceBidRequest $request, Bid $bid): RedirectResponse
    {
        $validated = $request->validated();

        $user = $request->user();
        $msisdn = $user->msisdn;
        $points = $validated['points'];

        if ($points <= 0) {
            return back()->withErrors(['points' => 'You cannot bid with 0 points']);
        }

        $userPoints = ActivePoint::where('msisdn', $msisdn)->first();

        if (! $userPoints) {
            return back()->withErrors(['points' => 'User does not have active points']);
        }

        if ($points > $userPoints->points) {
            return back()->withErrors(['points' => 'Exhausted Points']);
        }

        $winnerMsisdn = null;

        DB::transaction(function () use ($msisdn, $points, $bid, $userPoints, &$winnerMsisdn) {
            // Update user points
            $new_points = $userPoints->points - $points;
            $userPoints->update(['points' => $new_points]);

            // Log point deduction
            Point::create([
                'msisdn' => $msisdn,
                'description' => 'debit|Bidded with '.$points,
                'points' => $points,
                'status' => 'debit',
            ]);

            // Check if the bid has an active record
            $bidActive = BidActive::where('bidid', $bid->id)->orderBy('id', 'desc')->first();

            if ($bidActive) {
                $update_active_bidpoints = $bidActive->points + $points;

                // Calculate hours past since the bid became active
                $dayinpass = $bidActive->created_at;
                $today = now();
                $active_bid_timer = floor(abs($today->diffInSeconds($dayinpass)) / 60 / 60);

                // Insert the new bid entry
                BidEntry::create([
                    'msisdn' => $msisdn,
                    'bidid' => $bid->id,
                    'points' => $points,
                ]);

                // Check if the open date limit has been reached and the active record is still open
                if ($active_bid_timer >= $bid->open_date && $bidActive->status === 0) {
                    $highestBidder = BidEntry::selectRaw('SUM(points) as total_points, msisdn')
                        ->where('bidid', $bid->id)
                        ->groupBy('msisdn')
                        ->orderByDesc('total_points')
                        ->first();

                    if ($highestBidder) {
                        BidWinner::create([
                            'msisdn' => $highestBidder->msisdn,
                            'bidid' => $bid->id,
                            'total_points' => $highestBidder->total_points,
                        ]);

                        $winnerMsisdn = $highestBidder->msisdn;
                    }

                    $bid->update(['status' => BidStatus::Closed]);
                    $bidActive->update(['msisdn' => $highestBidder->msisdn, 'points' => $update_active_bidpoints, 'status' => 1]);
                } else {
                    $bidActive->update(['points' => $update_active_bidpoints]);
                }
            } else {
                // If there's no active record, meaning the open points target hasn't been met yet
                BidEntry::create([
                    'msisdn' => $msisdn,
                    'bidid' => $bid->id,
                    'points' => $points,
                ]);

                $carrygo_bid_entry_sum = BidEntry::where('bidid', $bid->id)->sum('points');

                if ($carrygo_bid_entry_sum >= $bid->open_points) {
                    BidActive::create([
                        'msisdn' => '', // Following legacy behaviour, MSISDN may be empty when making it active generally
                        'bidid' => $bid->id,
                        'points' => $carrygo_bid_entry_sum,
                        'status' => 0,
                    ]);
                }
            }
        });

        // Evaluate achievements after transaction completes
        $this->achievementService->evaluate($user, 'bid_placed', [
            'category' => $bid->category,
        ]);
        $this->achievementService->evaluate($user, 'points_deducted', [
            'points_spent' => $points,
        ]);

        // If a winner was determined, evaluate their bid_won achievement
        if ($winnerMsisdn !== null) {
            $winner = User::where('msisdn', $winnerMsisdn)->first();
            if ($winner) {
                $this->achievementService->evaluate($winner, 'bid_won');
            }
        }

        return back()->with('success', 'Bid placed successfully.');
    }
}
