<?php

namespace App\Http\Controllers;

use App\Enums\BidStatus;
use App\Models\Bid;
use App\Models\BidEntry;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class TrendingBidsController extends Controller
{
    public function index(Request $request): Response
    {
        /** @var User $user */
        $user = Auth::user();

        $categories = Bid::query()
            ->whereNotNull('category')
            ->where('category', '!=', '')
            ->distinct()
            ->orderBy('category')
            ->pluck('category')
            ->values();

        $statuses = match ($request->status) {
            'live' => [BidStatus::Live],
            'upcoming' => [BidStatus::Upcoming],
            default => [BidStatus::Upcoming, BidStatus::Live],
        };

        $bids = Bid::query()
            ->with('bidActive')
            ->whereIn('status', $statuses)
            ->withSum('bidEntries as bid_entry_points', 'points')
            ->withSum('bidActives as bid_active_points', 'points')
            ->has('bidEntries')
            ->when($request->search, fn ($q, $s) => $q->where(fn ($q) => $q->where('name', 'like', "%{$s}%")
                ->orWhere('price', 'like', "%{$s}%")
            )
            )
            ->when($request->category, fn ($q, $category) => $q->where('category', $category))
            ->when(
                $request->sort === 'value_desc',
                fn ($q) => $q->orderByDesc('price'),
                fn ($q) => $q->orderByDesc(
                    BidEntry::select('created_at')
                        ->whereColumn('bidid', 'carrygo_bid.id')
                        ->latest()
                        ->limit(1)
                ),
            )
            ->paginate(20)
            ->withQueryString();

        $bids->getCollection()->transform(function (Bid $bid) {
            $bid->ends_at = in_array($bid->status, [BidStatus::Live, BidStatus::Closed], true)
                ? $bid->bidActive?->created_at?->copy()->addHours((int) $bid->open_date)?->toISOString()
                : null;

            return $bid;
        });

        return Inertia::render('TrendingBids', [
            'bids' => $bids,
            'categories' => $categories,
            'userPoints' => $user->activePoint?->points,
        ]);
    }
}
