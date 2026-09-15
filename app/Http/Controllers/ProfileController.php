<?php

namespace App\Http\Controllers;

use App\Enums\BidStatus;
use App\Models\Bid;
use App\Models\BidEntry;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ProfileController extends Controller
{
    public function show(Request $request): Response
    {
        $user = $request->user();

        $myBids = Bid::query()
            ->whereHas('bidEntries', function ($query) use ($user) {
                $query->where('msisdn', $user->msisdn);
            })
            ->withSum(['bidEntries as user_total_points' => function ($query) use ($user) {
                $query->where('msisdn', $user->msisdn);
            }], 'points')
            ->withSum('bidEntries as bid_entry_points', 'points')
            ->withSum('bidActives as bid_active_points', 'points')
            ->with(['bidActive', 'bidWinner'])
            ->orderByDesc(
                BidEntry::select('created_at')
                    ->whereColumn('bidid', 'carrygo_bid.id')
                    ->where('msisdn', $user->msisdn)
                    ->latest()
                    ->limit(1)
            )
            ->paginate(10)
            ->withQueryString();

        $myBids->getCollection()->transform(function (Bid $bid) {
            $bid->ends_at = in_array($bid->status, [BidStatus::Live], true)
                ? $bid->bidActive?->created_at?->copy()->addHours((int) $bid->open_date)?->toISOString()
                : null;

            return $bid;
        });

        return Inertia::render('Profile', [
            'user' => [
                'msisdn' => $user->msisdn,
                'referral_code' => $user->referral_code,
                'points' => $user->activePoint?->points ?? 0,
            ],
            'referral_url' => route('login', ['ref' => $user->referral_code]),
            'myBids' => $myBids,
        ]);
    }
}
