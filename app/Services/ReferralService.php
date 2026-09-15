<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;

class ReferralService
{
    public function __construct(
        private readonly RewardsService $rewardsService,
        private readonly AchievementService $achievementService,
    ) {}

    /**
     * @return array{processed: bool, reason: string|null}
     */
    public function processAfterLogin(User $referee, string $referralCode): array
    {
        $normalizedCode = strtoupper(trim($referralCode));

        if ($normalizedCode === '') {
            return ['processed' => false, 'reason' => 'empty_code'];
        }

        if (strtoupper((string) $referee->referral_code) === $normalizedCode) {
            return ['processed' => false, 'reason' => 'self_referral'];
        }

        if ($referee->referred_by_user_id !== null) {
            return ['processed' => false, 'reason' => 'already_referred'];
        }

        $referrer = User::query()
            ->where('referral_code', $normalizedCode)
            ->first();

        if ($referrer === null) {
            return ['processed' => false, 'reason' => 'invalid_code'];
        }

        $refereePoints = (int) config('rewards.referral.referee_points', 100);
        $referrerPoints = (int) config('rewards.referral.referrer_points', 50);

        DB::transaction(function () use ($referee, $referrer, $refereePoints, $referrerPoints): void {
            $referee->update(['referred_by_user_id' => $referrer->id]);

            $this->rewardsService->credit(
                $referee->msisdn,
                $refereePoints,
                'referral',
                'Referral welcome bonus'
            );

            $this->rewardsService->credit(
                $referrer->msisdn,
                $referrerPoints,
                'referral',
                'Referral reward for inviting '.$referee->msisdn
            );
        });

        $referee->refresh();
        $referrer->refresh();

        $this->achievementService->evaluate($referee, 'referral_joined');
        $this->achievementService->evaluate($referrer, 'referral_completed');

        return ['processed' => true, 'reason' => null];
    }
}
