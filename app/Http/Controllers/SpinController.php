<?php

namespace App\Http\Controllers;

use App\Services\RewardsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SpinController extends Controller
{
    public function __construct(private readonly RewardsService $rewardsService) {}

    public function store(Request $request): JsonResponse
    {
        $user = $request->user();

        if ($user->spins_balance < 1) {
            return response()->json(['error' => 'No spins available.'], 422);
        }

        try {
            $result = $this->rewardsService->spinWheel($user);
        } catch (\RuntimeException $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }

        return response()->json([
            'points_won' => $result['points_won'],
            'segment_index' => $result['segment_index'],
            'message' => '🎉 You won '.$result['points_won'].' pts! Claim them from your wallet.',
        ]);
    }
}
