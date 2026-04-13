<?php

namespace App\Http\Controllers;

use App\Enums\BidStatus;
use App\Models\Bid;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class HistoryController extends Controller
{
    public function index(Request $request): Response
    {
        /** @var User $user */
        $user = Auth::user();

        $bids = Bid::query()
            ->with(['bidWinner', 'reviews'])
            ->where('status', BidStatus::Closed)
            ->withSum('bidEntries as bid_entry_points', 'points')
            ->when($request->search, function ($q, $s) {
                $q->where(function ($q) use ($s) {
                    $q->where('name', 'like', "%{$s}%")
                        ->orWhere('price', 'like', "%{$s}%");
                });
            })
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('HistoryBids', [
            'bids' => $bids,
            'userPoints' => $user->activePoint?->points ?? 0,
        ]);
    }

    public function storeReview(Request $request, Bid $bid): RedirectResponse
    {
        $validated = $request->validate([
            'rating' => ['required', 'integer', 'min:1', 'max:5'],
            'comment' => ['required', 'string', 'max:2000'],
        ]);

        /** @var User $user */
        $user = Auth::user();

        $bid->reviews()->create([
            'user_id' => $user->msisdn,
            'rating' => $validated['rating'],
            'comment' => $validated['comment'],
        ]);

        return redirect()->back()->with('success', 'Review submitted successfully!');
    }
}
