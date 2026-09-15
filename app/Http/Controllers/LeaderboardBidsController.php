<?php

namespace App\Http\Controllers;

use App\Enums\BidStatus;
use App\Models\Bid;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class LeaderboardBidsController extends Controller
{
    public function index(Request $request): Response
    {
        // We order by the max points a single user has bidded on an item
        $sumQuery = DB::table('carrygo_bid_entry')
            ->select('bidid', 'msisdn', DB::raw('SUM(points) as total_points'))
            ->groupBy('bidid', 'msisdn');

        // We wrap sumQuery natively to find the max points per bid
        $maxQuery = DB::table(DB::raw("({$sumQuery->toSql()}) as x"))
            ->select('bidid', DB::raw('MAX(total_points) as max_points'))
            ->groupBy('bidid');

        $bids = Bid::query()
            ->leftJoinSub($maxQuery, 'm', 'carrygo_bid.id', '=', 'm.bidid')
            ->where('status', BidStatus::Live)
            ->when($request->search, function ($q, $s) {
                $q->where(function ($sub) use ($s) {
                    $sub->where('name', 'like', "%{$s}%")
                        ->orWhere('price', 'like', "%{$s}%");
                });
            })
            ->select('carrygo_bid.*')
            ->orderByRaw('COALESCE(m.max_points, 0) DESC')
            ->orderByDesc('carrygo_bid.id')
            ->paginate(20)
            ->withQueryString();

        $bidIds = $bids->pluck('id');

        // Fetch top 3 aggregated entries per bid
        if ($bidIds->isNotEmpty()) {
            $leaderboard = DB::table('carrygo_bid_entry')
                ->whereIn('bidid', $bidIds)
                ->select('bidid', 'msisdn', DB::raw('SUM(points) as total_points'))
                ->groupBy('bidid', 'msisdn')
                // Order globally descending so when we groupBy bidid, the top ones are first
                ->orderByDesc('total_points')
                ->get()
                ->groupBy('bidid')
                ->map(fn ($entries) => $entries->take(3)->values());

            $bids->getCollection()->transform(function (Bid $bid) use ($leaderboard) {
                // Attach the top bidders dynamically as a property Inertia will serialize
                $bid->setAttribute('top_bidders', $leaderboard->get($bid->id, collect())->toArray());

                return $bid;
            });
        }

        return Inertia::render('LeaderboardBids', [
            'bids' => $bids,
        ]);
    }
}
