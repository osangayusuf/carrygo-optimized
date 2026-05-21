<?php

namespace App\Http\Controllers;

use App\Models\UserAnalytics;
use App\Services\RewardsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class RewardClaimController extends Controller
{
    public function __construct(private readonly RewardsService $rewardsService) {}

    public function store(Request $request): RedirectResponse
    {
        $user = $request->user();
        $totalClaimed = $this->rewardsService->claimAll($user);

        if ($totalClaimed === 0) {
            return back()->with('error', 'You have no unclaimed rewards.');
        }

        UserAnalytics::query()->create([
            'msisdn' => $user->msisdn,
            'action' => 'rewards_claim',
            'activity_date' => now(),
        ]);

        return back()->with('success', '✅ '.$totalClaimed.' points added to your balance!');
    }
}
