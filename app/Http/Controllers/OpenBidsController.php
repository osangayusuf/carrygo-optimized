<?php

namespace App\Http\Controllers;

use App\Enums\BidStatus;
use App\Models\Bid;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class OpenBidsController extends Controller
{
    public function index(Request $request): Response
    {
        /** @var User|null $user */
        $user = Auth::user();

        $bids = Bid::query()
            ->with('bidActive')
            ->where('status', BidStatus::Live)
            ->has('bidActive')
            ->withSum('bidActives as bid_active_points', 'points')
            ->withSum('bidEntries as bid_entry_points', 'points')
            ->when($request->search, fn ($q, $s) => $q->where(fn ($q) => $q->where('name', 'like', "%{$s}%")
                ->orWhere('price', 'like', "%{$s}%")
            ))
            ->orderByDesc('bid_active_points')
            ->paginate(20)
            ->withQueryString();

        $bids->getCollection()->transform(function (Bid $bid) {
            $bid->ends_at = in_array($bid->status, [BidStatus::Live, BidStatus::Closed], true)
                ? $bid->bidActive?->created_at?->copy()->addHours((int) $bid->open_date)?->toISOString()
                : null;

            return $bid;
        });

        return Inertia::render('OpenBids', [
            'bids' => $bids,
            'userPoints' => $user?->activePoint?->points,
        ]);
    }
}
