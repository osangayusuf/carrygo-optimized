<?php

namespace App\Http\Controllers;

use App\Enums\BidStatus;
use App\Models\Bid;
use App\Models\BidEntry;
use App\Models\BidWinner;
use App\Models\Review;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class HomeController extends Controller
{
    public function index(Request $request): Response
    {
        $categories = Bid::query()
            ->whereNotNull('category')
            ->where('category', '!=', '')
            ->distinct()
            ->orderBy('category')
            ->pluck('category')
            ->values();

        $baseQ = Bid::query()->when($request->search, function ($q, $search) {
            $q->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('price', 'like', "%{$search}%");
            });
        })
            ->where('status', BidStatus::Live);
            
        $bids = (clone $baseQ)
            ->with(['bidActive'])
            ->withSum('bidEntries as bid_entry_points', 'points')
            ->withSum('bidActives as bid_active_points', 'points')
            ->inRandomOrder()
            ->limit(10)
            ->get(['id', 'name', 'image', 'url', 'price', 'open_points', 'rating', 'open_date', 'status', 'created_at']);

        $bids->transform(function (Bid $bid) {
            $bid->ends_at = in_array($bid->status, [BidStatus::Live], true)
                ? $bid->bidActive?->created_at?->copy()->addHours((int) $bid->open_date)?->toISOString()
                : null;

            return $bid;
        });

        $winners = BidWinner::query()
            ->with(['bid:id,name,image,url,price'])
            ->orderByDesc('id')
            ->limit(5)
            ->get(['id', 'msisdn', 'total_points', 'bidid', 'created_at']);

        /** @var array{enabled: bool, bid_id: int|null} $popupConfig */
        $popupConfig = config('promotions.winner_popup');
        $winnerPopup = null;

        if ($popupConfig['enabled'] && $popupConfig['bid_id']) {
            $winnerPopup = BidWinner::query()
                ->with(['bid:id,name,image'])
                ->where('bidid', $popupConfig['bid_id'])
                ->orderByDesc('id')
                ->first(['id', 'msisdn', 'total_points', 'bidid', 'created_at']);
        }

        /** @var array{enabled: bool, bid_id: int|null} $eventConfig */
        $eventConfig = config('promotions.event_popup');
        $eventPopupBid = null;

        if ($eventConfig['enabled'] && $eventConfig['bid_id']) {
            $eventPopupBid = Bid::query()
                ->with('bidActive')
                ->where('id', $eventConfig['bid_id'])
                ->where('status', BidStatus::Live)
                ->first(['id', 'name', 'image', 'url', 'price', 'open_points', 'rating', 'open_date', 'status', 'created_at']);

            if ($eventPopupBid) {
                $eventPopupBid->ends_at = in_array($eventPopupBid->status, [BidStatus::Live], true)
                    ? $eventPopupBid->bidActive?->created_at?->copy()->addHours((int) $eventPopupBid->open_date)?->toISOString()
                    : null;
            }
        }

        /** @var User|null $authUser */
        $authUser = Auth::user();
        $userPoints = $authUser?->activePoint?->points;

        $reviews = Review::query()
            ->where('moderation_status', 'approved')
            ->where('rating', 5)
            ->with(['bid:id,name,image,url', 'user:id,msisdn'])
            ->inRandomOrder()
            ->limit(6)
            ->get();

        $trendingBids = (clone $baseQ)
            ->with('bidActive')
            ->withSum('bidEntries as bid_entry_points', 'points')
            ->withSum('bidActives as bid_active_points', 'points')
            ->has('bidEntries')
            ->orderByDesc(
                BidEntry::select('created_at')
                    ->whereColumn('bidid', 'carrygo_bid.id')
                    ->latest()
                    ->limit(1)
            )
            ->limit(10)
            ->get(['id', 'name', 'image', 'url', 'price', 'open_points', 'rating', 'open_date', 'status', 'created_at']);

        $trendingBids->transform(function (Bid $bid) {
            $bid->ends_at = in_array($bid->status, [BidStatus::Live], true)
                ? $bid->bidActive?->created_at?->copy()->addHours((int) $bid->open_date)?->toISOString()
                : null;

            return $bid;
        });

        $openBids = (clone $baseQ)->with('bidActive')
            ->where('status', BidStatus::Live)
            ->has('bidActive')
            ->withSum('bidActives as bid_active_points', 'points')
            ->withSum('bidEntries as bid_entry_points', 'points')
            ->orderByDesc('bid_active_points')
            ->limit(10)
            ->get(['id', 'name', 'image', 'url', 'price', 'open_points', 'rating', 'open_date', 'status', 'created_at']);

        $openBids->transform(function (Bid $bid) {
            $bid->ends_at = in_array($bid->status, [BidStatus::Live], true)
                ? $bid->bidActive?->created_at?->copy()->addHours((int) $bid->open_date)?->toISOString()
                : null;

            return $bid;
        });

        $topCategories = $categories->take(4);
        $categoryBids = [];
        foreach ($topCategories as $cat) {
            $catBids = (clone $baseQ)->with('bidActive')
                ->where('category', $cat)
                ->orderByDesc('id')
                ->limit(10)
                ->get(['id', 'name', 'image', 'url', 'price', 'open_points', 'rating', 'open_date', 'status', 'created_at']);

            $catBids->transform(function (Bid $bid) {
                $bid->ends_at = in_array($bid->status, [BidStatus::Live], true)
                    ? $bid->bidActive?->created_at?->copy()->addHours((int) $bid->open_date)?->toISOString()
                    : null;

                return $bid;
            });
            $categoryBids[$cat] = $catBids;
        }

        $luxuryBids = (clone $baseQ)->with('bidActive')
            ->orderByRaw("CAST(REPLACE(price, ',', '') AS UNSIGNED) DESC")
            ->limit(10)
            ->get(['id', 'name', 'image', 'url', 'price', 'open_points', 'rating', 'open_date', 'status', 'created_at']);

        $luxuryBids->transform(function (Bid $bid) {
            $bid->ends_at = in_array($bid->status, [BidStatus::Live], true)
                ? $bid->bidActive?->created_at?->copy()->addHours((int) $bid->open_date)?->toISOString()
                : null;

            return $bid;
        });

        return Inertia::render('Home', [
            'bids' => $bids,
            'trendingBids' => $trendingBids,
            'openBids' => $openBids,
            'luxuryBids' => $luxuryBids,
            'categoryBids' => $categoryBids,
            'categories' => $categories,
            'winners' => $winners,
            'userPoints' => $userPoints,
            'reviews' => $reviews,
            'winnerPopup' => $winnerPopup,
            'eventPopupBid' => $eventPopupBid,
        ]);
    }
}
