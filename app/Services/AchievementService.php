<?php

namespace App\Services;

use App\Models\User;
use App\Models\UserAchievement;
use Illuminate\Support\Facades\DB;

class AchievementService
{
    public function __construct(private readonly RewardsService $rewardsService) {}

    /**
     * Evaluate and grant achievements based on a trigger event.
     * All grants are idempotent — a completed achievement can never be granted twice.
     *
     * @param  string  $event  One of: 'bid_placed', 'bid_won', 'points_deducted', 'referral_completed', 'referral_joined'
     * @param  array{bid_id?: int, category?: string, points_spent?: int}  $context
     * @return array<string> Keys of newly completed achievements
     */
    public function evaluate(User $user, string $event, array $context = []): array
    {
        $newlyCompleted = [];

        match ($event) {
            'bid_placed' => $this->evaluateBidPlaced($user, $context, $newlyCompleted),
            'bid_won' => $this->evaluateBidWon($user, $newlyCompleted),
            'points_deducted' => $this->evaluatePointsDeducted($user, $context, $newlyCompleted),
            'referral_completed' => $this->evaluateReferralCompleted($user, $newlyCompleted),
            'referral_joined' => $this->evaluateReferralJoined($user, $newlyCompleted),
            default => null,
        };

        return $newlyCompleted;
    }

    /**
     * @param  array<string>  $newlyCompleted
     */
    private function evaluateBidPlaced(User $user, array $context, array &$newlyCompleted): void
    {
        // first_bid — complete on first bid_placed event
        $this->incrementAndGrant($user, 'first_bid', 1, $newlyCompleted);

        // explorer — count distinct categories the user has bid on
        if (isset($context['category'])) {
            $distinctCategories = DB::table('carrygo_bid_entry as be')
                ->join('carrygo_bid as b', 'be.bidid', '=', 'b.id')
                ->where('be.msisdn', $user->msisdn)
                ->whereNotNull('b.category')
                ->distinct()
                ->count('b.category');

            $config = config('rewards.achievements.explorer');
            $target = (int) ($config['target'] ?? 5);

            $achievement = UserAchievement::firstOrCreate(
                ['msisdn' => $user->msisdn, 'achievement_key' => 'explorer'],
                ['progress' => 0, 'completed_at' => null]
            );

            if (! $achievement->isCompleted()) {
                $achievement->update(['progress' => min($distinctCategories, $target)]);

                if ($distinctCategories >= $target) {
                    $this->grantAchievement($user, $achievement, 'explorer', $newlyCompleted);
                }
            }
        }
    }

    /**
     * @param  array<string>  $newlyCompleted
     */
    private function evaluateBidWon(User $user, array &$newlyCompleted): void
    {
        $this->incrementAndGrant($user, 'first_win', 1, $newlyCompleted);
    }

    /**
     * @param  array<string>  $newlyCompleted
     */
    private function evaluatePointsDeducted(User $user, array $context, array &$newlyCompleted): void
    {
        // big_spender — cumulative points spent as debits in the points ledger
        $config = config('rewards.achievements.big_spender');
        $target = (int) ($config['target'] ?? 500);

        $totalSpent = DB::table('carrygo_points')
            ->where('msisdn', $user->msisdn)
            ->where('status', 'debit')
            ->sum('points');

        $achievement = UserAchievement::firstOrCreate(
            ['msisdn' => $user->msisdn, 'achievement_key' => 'big_spender'],
            ['progress' => 0, 'completed_at' => null]
        );

        if (! $achievement->isCompleted()) {
            $achievement->update(['progress' => min((int) $totalSpent, $target)]);

            if ((int) $totalSpent >= $target) {
                $this->grantAchievement($user, $achievement, 'big_spender', $newlyCompleted);
            }
        }
    }

    /**
     * @param  array<string>  $newlyCompleted
     */
    private function evaluateReferralCompleted(User $user, array &$newlyCompleted): void
    {
        $config = config('rewards.achievements.refer_a_friend');
        $target = (int) ($config['target'] ?? 1);

        $referralCount = User::query()
            ->where('referred_by_user_id', $user->id)
            ->count();

        $achievement = UserAchievement::firstOrCreate(
            ['msisdn' => $user->msisdn, 'achievement_key' => 'refer_a_friend'],
            ['progress' => 0, 'completed_at' => null]
        );

        if ($achievement->isCompleted()) {
            return;
        }

        $achievement->update(['progress' => min($referralCount, $target)]);

        if ($referralCount >= $target) {
            $this->grantAchievement($user, $achievement, 'refer_a_friend', $newlyCompleted);
        }
    }

    /**
     * @param  array<string>  $newlyCompleted
     */
    private function evaluateReferralJoined(User $user, array &$newlyCompleted): void
    {
        if ($user->referred_by_user_id === null) {
            return;
        }

        $this->incrementAndGrant($user, 'join_via_referral', 1, $newlyCompleted);
    }

    /**
     * Increment an achievement's progress by 1 and grant if target is met.
     *
     * @param  array<string>  $newlyCompleted
     */
    private function incrementAndGrant(
        User $user,
        string $key,
        int $target,
        array &$newlyCompleted
    ): void {
        $achievement = UserAchievement::firstOrCreate(
            ['msisdn' => $user->msisdn, 'achievement_key' => $key],
            ['progress' => 0, 'completed_at' => null]
        );

        if ($achievement->isCompleted()) {
            return;
        }

        $newProgress = $achievement->progress + 1;
        $achievement->update(['progress' => $newProgress]);

        if ($newProgress >= $target) {
            $this->grantAchievement($user, $achievement, $key, $newlyCompleted);
        }
    }

    /**
     * Mark an achievement as completed and credit points to the reward wallet.
     *
     * @param  array<string>  $newlyCompleted
     */
    private function grantAchievement(
        User $user,
        UserAchievement $achievement,
        string $key,
        array &$newlyCompleted
    ): void {
        $achievement->update(['completed_at' => now()]);

        $config = config('rewards.achievements.'.$key, []);
        $points = (int) ($config['points'] ?? 0);

        if ($points > 0) {
            $this->rewardsService->credit(
                $user->msisdn,
                $points,
                'achievement',
                'Achievement unlocked: '.($config['label'] ?? $key)
            );
        }

        $newlyCompleted[] = $key;
    }
}
