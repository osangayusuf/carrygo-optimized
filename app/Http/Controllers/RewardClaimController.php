<?php

namespace App\Http\Controllers;

use App\Services\RewardsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class RewardClaimController extends Controller
{
    public function __construct(private readonly RewardsService $rewardsService) {}

    public function store(Request $request): RedirectResponse
    {
        $totalClaimed = $this->rewardsService->claimAll($request->user());

        if ($totalClaimed === 0) {
            return back()->with('error', 'You have no unclaimed rewards.');
        }

        return back()->with('success', '✅ '.$totalClaimed.' points added to your balance!');
    }
}
