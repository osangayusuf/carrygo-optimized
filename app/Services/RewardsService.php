<?php

namespace App\Services;

use App\Models\ActivePoint;
use App\Models\LeaderboardSnapshot;
use App\Models\Point;
use App\Models\RewardWallet;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class RewardsService
{
    /**
     * Add a pending reward to the wallet.
     * Does NOT touch carrygo_active_points — user must claim separately.
     */
    public function credit(string $msisdn, int $points, string $source, string $description): RewardWallet
    {
        return RewardWallet::create([
            'msisdn' => $msisdn,
            'source' => $source,
            'description' => $description,
            'points' => $points,
            'claimed_at' => null,
        ]);
    }

    /**
     * Claim all unclaimed rewards for a user.
     * Transfers total to carrygo_active_points and logs to carrygo_points ledger.
     * Returns total points claimed (0 if nothing to claim).
     */
    public function claimAll(User $user): int
    {
        $unclaimed = RewardWallet::where('msisdn', $user->msisdn)
            ->whereNull('claimed_at')
            ->lockForUpdate()
            ->get();

        if ($unclaimed->isEmpty()) {
            return 0;
        }

        $total = $unclaimed->sum('points');

        DB::transaction(function () use ($user, $total) {
            // Mark all as claimed
            RewardWallet::where('msisdn', $user->msisdn)
                ->whereNull('claimed_at')
                ->update(['claimed_at' => now()]);

            // Credit carrygo_active_points (upsert in case record exists)
            $activePoint = ActivePoint::where('msisdn', $user->msisdn)->first();

            if ($activePoint) {
                $activePoint->increment('points', $total);
            } else {
                ActivePoint::create([
                    'msisdn' => $user->msisdn,
                    'points' => $total,
                ]);
            }

            // Write ledger credit entry
            Point::create([
                'msisdn' => $user->msisdn,
                'description' => 'credit|Rewards claimed: '.$total.' pts',
                'points' => $total,
                'status' => 'credit',
            ]);
        });

        return $total;
    }

    /**
     * Process a daily check-in for a user.
     * Idempotent — safe to call multiple times a day (will return already_checked_in).
     *
     * @return array{points: int, streak: int, milestone_bonus: int, already_checked_in: bool}
     */
    public function processCheckin(User $user): array
    {
        $today = Carbon::today();

        // Guard: already checked in today
        if ($user->last_checkin_date && Carbon::parse($user->last_checkin_date)->eq($today)) {
            return [
                'points' => 0,
                'streak' => $user->checkin_streak,
                'milestone_bonus' => 0,
                'already_checked_in' => true,
            ];
        }

        $yesterday = Carbon::yesterday();
        $wasYesterday = $user->last_checkin_date && Carbon::parse($user->last_checkin_date)->eq($yesterday);
        $newStreak = $wasYesterday ? $user->checkin_streak + 1 : 1;

        $basePoints = (int) config('rewards.checkin.base_points', 2);
        $milestones = config('rewards.checkin.streak_milestones', []);
        $milestoneBonus = (int) ($milestones[$newStreak] ?? 0);
        $totalPoints = $basePoints + $milestoneBonus;

        DB::transaction(function () use ($user, $today, $newStreak, $totalPoints) {
            $user->update([
                'checkin_streak' => $newStreak,
                'last_checkin_date' => $today->toDateString(),
            ]);

            $description = 'Daily check-in (streak: '.$newStreak.' days)';
            $this->credit($user->msisdn, $totalPoints, 'checkin', $description);
        });

        return [
            'points' => $totalPoints,
            'streak' => $newStreak,
            'milestone_bonus' => $milestoneBonus,
            'already_checked_in' => false,
        ];
    }

    /**
     * Execute a spin. Decrements spins_balance and adds reward to wallet.
     * Uses server-side weighted random — probabilities never exposed to frontend.
     *
     * @return array{points_won: int, segment_index: int}
     *
     * @throws \RuntimeException when no spins available
     */
    public function spinWheel(User $user): array
    {
        $result = DB::transaction(function () use ($user) {
            // Lock the user row to prevent concurrent spins
            $fresh = User::where('msisdn', $user->msisdn)->lockForUpdate()->first();

            if ($fresh->spins_balance < 1) {
                throw new \RuntimeException('No spins available.');
            }

            $segments = config('rewards.spin_wheel.segments', []);
            $segmentIndex = $this->pickWeightedSegment($segments);
            $pointsWon = (int) $segments[$segmentIndex]['points'];

            $fresh->decrement('spins_balance');

            $this->credit(
                $user->msisdn,
                $pointsWon,
                'spin',
                'Spin the wheel reward: '.$pointsWon.' pts'
            );

            return ['points_won' => $pointsWon, 'segment_index' => $segmentIndex];
        });

        return $result;
    }

    /**
     * Grant 1 free spin to every user.
     * Called nightly by the scheduler.
     */
    public function grantDailySpins(): void
    {
        $spinsPerDay = (int) config('rewards.spin_wheel.free_spins_per_day', 1);

        DB::table('carrygo_users')->update([
            'spins_balance' => DB::raw("spins_balance + {$spinsPerDay}"),
        ]);
    }

    /**
     * Compute the weekly leaderboard, snapshot results, award bonus rewards.
     * Leaderboard metric: total points spent bidding in the current ISO week.
     */
    public function processWeeklyLeaderboard(): void
    {
        $weekStart = Carbon::now()->startOfWeek()->toDateString();

        $bonuses = config('rewards.leaderboard.weekly_bonuses', []);

        $topBidders = DB::table('carrygo_points')
            ->where('status', 'debit')
            ->where('created_at', '>=', Carbon::now()->startOfWeek())
            ->select('msisdn', DB::raw('SUM(points) as total_bid_pts'))
            ->groupBy('msisdn')
            ->orderByDesc('total_bid_pts')
            ->limit(count($bonuses))
            ->get();

        DB::transaction(function () use ($topBidders, $weekStart, $bonuses) {
            foreach ($topBidders as $rank => $bidder) {
                $rankNumber = $rank + 1;
                $bonusPoints = (int) ($bonuses[$rankNumber] ?? 0);

                if ($bonusPoints === 0) {
                    continue;
                }

                LeaderboardSnapshot::create([
                    'msisdn' => $bidder->msisdn,
                    'rank' => $rankNumber,
                    'total_bid_pts' => (int) $bidder->total_bid_pts,
                    'week_start' => $weekStart,
                    'bonus_awarded' => $bonusPoints,
                ]);

                $this->credit(
                    $bidder->msisdn,
                    $bonusPoints,
                    'leaderboard',
                    'Weekly leaderboard rank #'.$rankNumber.' bonus'
                );
            }
        });
    }

    /**
     * Select a segment index using weighted random selection.
     *
     * @param  array<int, array{points: int, probability: int}>  $segments
     */
    private function pickWeightedSegment(array $segments): int
    {
        $total = array_sum(array_column($segments, 'probability'));
        $random = random_int(1, $total);
        $cumulative = 0;

        foreach ($segments as $index => $segment) {
            $cumulative += $segment['probability'];
            if ($random <= $cumulative) {
                return $index;
            }
        }

        // Fallback to last segment
        return count($segments) - 1;
    }
}
