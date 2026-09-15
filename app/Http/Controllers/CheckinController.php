<?php

namespace App\Http\Controllers;

use App\Models\UserAnalytics;
use App\Services\RewardsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class CheckinController extends Controller
{
    public function __construct(private readonly RewardsService $rewardsService) {}

    public function store(Request $request): RedirectResponse
    {
        $user = $request->user();
        $result = $this->rewardsService->processCheckin($user);

        if ($result['already_checked_in']) {
            return back()->with('error', 'You have already checked in today. Come back tomorrow!');
        }

        $message = '🎉 Checked in! You earned '.$result['points'].' pts';

        if ($result['milestone_bonus'] > 0) {
            $message .= ' (including a '.$result['milestone_bonus'].' pt streak bonus!)';
        }

        $message .= ' — '.$result['streak'].' day streak';

        UserAnalytics::query()->create([
            'msisdn' => $user->msisdn,
            'action' => 'daily_check_in',
            'activity_date' => now(),
        ]);

        return back()->with('success', $message);
    }
}
