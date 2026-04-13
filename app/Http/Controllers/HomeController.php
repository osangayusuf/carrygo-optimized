<?php

namespace App\Http\Controllers;

use App\Enums\BidStatus;
use App\Models\Bid;
use App\Models\BidWinner;
use App\Models\Review;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class HomeController extends Controller
{
    public function index(): Response
    {
        $heroPool = Bid::query()
            ->whereIn('status', [BidStatus::Upcoming, BidStatus::Live]);

        $featuredPool = (clone $heroPool)
            ->where('event_special', true);

        $heroQuery = (clone $featuredPool)->exists()
            ? $featuredPool
            : $heroPool;

        $heroCount = (clone $heroQuery)->count();
        $heroBid = null;

        if ($heroCount > 0) {
            $seed = abs(crc32(now()->toDateString()));
            $offset = $seed % $heroCount;

            $heroBid = (clone $heroQuery)
                ->orderBy('id')
                ->offset($offset)
                ->limit(1)
                ->first([
                    'id',
                    'name',
                    'image',
                    'url',
                    'price',
                    'open_points',
                    'rating',
                    'open_date',
                    'status',
                    'created_at',
                ]);
        }

        $bids = Bid::query()
            ->with('bidActive')
            ->whereIn('status', [BidStatus::Upcoming, BidStatus::Live])
            ->withSum('bidEntries as bid_entry_points', 'points')
            ->withSum('bidActives as bid_active_points', 'points')
            ->orderByDesc('id')
            ->limit(12)
            ->get(['id', 'name', 'image', 'url', 'price', 'open_points', 'rating', 'open_date', 'status', 'created_at']);

        $bids->transform(function (Bid $bid) {
            $bid->ends_at = in_array($bid->status, [BidStatus::Live, BidStatus::Closed], true)
                ? $bid->bidActive?->created_at?->copy()->addHours((int) $bid->open_date)?->toISOString()
                : null;

            return $bid;
        });

        $winners = BidWinner::query()
            ->with(['bid:id,name,image,url,price'])
            ->orderByDesc('id')
            ->get(['id', 'msisdn', 'total_points', 'bidid', 'created_at']);

        /** @var User|null $authUser */
        $authUser = Auth::user();
        $userPoints = $authUser?->activePoint?->points;

        $reviews = Review::query()
            ->with(['bid:id,name,image,url', 'user:id,msisdn'])
            ->orderByDesc('rating')
            ->orderByDesc('id')
            ->limit(6)
            ->get(['id', 'user_id', 'rating', 'comment', 'bidid', 'created_at']);

        return Inertia::render('Home', [
            'heroBid' => $heroBid,
            'bids' => $bids,
            'winners' => $winners,
            'userPoints' => $userPoints,
            'reviews' => $reviews,
        ]);
    }
}
