<?php

namespace App\Http\Controllers;

use App\Enums\BidStatus;
use App\Models\Bid;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class EventBidsController extends Controller
{
    public function index(Request $request): Response
    {
        /** @var User $user */
        $user = Auth::user();

        $bids = Bid::query()
            ->with(['bidActive'])
            ->where('event_special', true)
            ->whereIn('status', [BidStatus::Upcoming, BidStatus::Live])
            ->withSum('bidEntries as bid_entry_points', 'points')
            ->withSum('bidActives as bid_active_points', 'points')
            ->when($request->search, fn ($q, $s) => $q->where(fn ($q) => $q->where('name', 'like', "%{$s}%")
                ->orWhere('price', 'like', "%{$s}%")
            ))
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString();

        $bids->getCollection()->transform(function (Bid $bid) {
            $bid->ends_at = in_array($bid->status, [BidStatus::Live, BidStatus::Closed], true)
                ? $bid->bidActive?->created_at?->copy()->addHours((int) $bid->open_date)?->toISOString()
                : null;

            return $bid;
        });

        return Inertia::render('EventBids', [
            'bids' => $bids,
            'userPoints' => $user->activePoint?->points,
        ]);
    }
}
