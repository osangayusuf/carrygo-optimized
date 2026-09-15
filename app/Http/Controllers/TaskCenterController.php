<?php

namespace App\Http\Controllers;

use App\Models\RewardWallet;
use App\Models\User;
use App\Models\UserAchievement;
use App\Services\AchievementService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class TaskCenterController extends Controller
{
    public function __construct(private readonly AchievementService $achievementService) {}

    public function index(Request $request): Response
    {
        $user = $request->user();
        $msisdn = $user->msisdn;

        $this->achievementService->evaluate($user, 'referral_completed');
        $this->achievementService->evaluate($user, 'referral_joined');

        // ── Check-in ──────────────────────────────────────────────────────
        $today = Carbon::today();
        $startOfWeek = Carbon::now()->startOfWeek(); // Monday
        $checkinStreak = (int) $user->checkin_streak;
        $lastCheckin = $user->last_checkin_date ? Carbon::parse($user->last_checkin_date) : null;
        $canCheckin = ! ($lastCheckin && $lastCheckin->eq($today));

        $weekDays = collect(range(0, 6))->map(function (int $offset) use ($startOfWeek, $msisdn) {
            $day = $startOfWeek->copy()->addDays($offset);
            $dateStr = $day->toDateString();

            // Check if user checked in on this day (look for a checkin wallet entry)
            $checked = RewardWallet::where('msisdn', $msisdn)
                ->where('source', 'checkin')
                ->whereDate('created_at', $dateStr)
                ->exists();

            return [
                'label' => $day->format('D'),
                'date' => $dateStr,
                'checked' => $checked,
                'is_today' => $day->isToday(),
            ];
        })->values()->all();

        // ── Achievements ───────────────────────────────────────────────
        $achievementConfig = config('rewards.achievements', []);
        $userAchievements = UserAchievement::where('msisdn', $msisdn)
            ->get()
            ->keyBy('achievement_key');

        $achievements = collect($achievementConfig)->map(function (array $cfg, string $key) use ($userAchievements) {
            $record = $userAchievements->get($key);

            return [
                'key' => $key,
                'label' => $cfg['label'],
                'description' => $cfg['description'],
                'icon' => $cfg['icon'],
                'points' => $cfg['points'],
                'target' => $cfg['target'],
                'progress' => $record?->progress ?? 0,
                'completed_at' => $record?->completed_at?->toIso8601String(),
            ];
        })->values()->all();

        // ── Spin ──────────────────────────────────────────────────────────
        $segmentLabels = collect(config('rewards.spin_wheel.segments', []))
            ->map(fn (array $s) => $s['points'].' pts')
            ->values()
            ->all();

        // ── Leaderboard ───────────────────────────────────────────────────
        $weekStart = Carbon::now()->startOfWeek();
        $nextSunday = Carbon::now()->endOfWeek()->setTime(23, 55, 0);

        $topUsers = DB::table('carrygo_points')
            ->where('status', 'debit')
            ->where('created_at', '>=', $weekStart)
            ->select('msisdn', DB::raw('SUM(points) as total_bid_pts'))
            ->groupBy('msisdn')
            ->orderByDesc('total_bid_pts')
            ->limit(3)
            ->get()
            ->map(function (object $row, int $index): array {
                return [
                    'rank' => $index + 1,
                    'msisdn_masked' => $this->maskMsisdn($row->msisdn),
                    'total_bid_pts' => (int) $row->total_bid_pts,
                ];
            })
            ->values()
            ->all();

        // Current user's own rank this week
        $allRanks = DB::table('carrygo_points')
            ->where('status', 'debit')
            ->where('created_at', '>=', $weekStart)
            ->select('msisdn', DB::raw('SUM(points) as total_bid_pts'))
            ->groupBy('msisdn')
            ->orderByDesc('total_bid_pts')
            ->get()
            ->values();

        $userRank = $allRanks->search(fn ($row) => $row->msisdn === $msisdn);
        $userRank = $userRank !== false ? $userRank + 1 : null;

        // ── Wallet ────────────────────────────────────────────────────────
        $unclaimedPoints = RewardWallet::where('msisdn', $msisdn)
            ->whereNull('claimed_at')
            ->sum('points');

        $recentRewards = RewardWallet::where('msisdn', $msisdn)
            ->orderByDesc('created_at')
            ->limit(10)
            ->get()
            ->map(fn (RewardWallet $r) => [
                'source' => $r->source,
                'description' => $r->description,
                'points' => $r->points,
                'claimed_at' => $r->claimed_at?->toIso8601String(),
                'created_at' => $r->created_at->toIso8601String(),
            ])
            ->values()
            ->all();

        return Inertia::render('TaskCenter', [
            'checkin' => [
                'streak' => $checkinStreak,
                'last_date' => $lastCheckin?->toDateString(),
                'can_checkin' => $canCheckin,
                'week_days' => $weekDays,
            ],
            'achievements' => $achievements,
            'spin' => [
                'available_spins' => (int) $user->spins_balance,
                'segment_labels' => $segmentLabels,
            ],
            'leaderboard' => [
                'top_users' => $topUsers,
                'week_ends_at' => $nextSunday->toIso8601String(),
                'user_rank' => $userRank,
            ],
            'wallet' => [
                'unclaimed_points' => (int) $unclaimedPoints,
                'recent_rewards' => $recentRewards,
            ],
            'user_points' => (int) ($user->activePoint?->points ?? 0),
            'referral' => [
                'referee_points' => (int) config('rewards.referral.referee_points', 100),
                'referrer_points' => (int) config('rewards.referral.referrer_points', 50),
                'referee_reward_label' => (string) config('rewards.referral.referee_reward_label', 'Your friend receives'),
                'referrer_reward_label' => (string) config('rewards.referral.referrer_reward_label', 'You receive'),
                'referrals_count' => User::query()->where('referred_by_user_id', $user->id)->count(),
            ],
        ]);
    }

    private function maskMsisdn(string $msisdn): string
    {
        $len = strlen($msisdn);

        if ($len <= 6) {
            return $msisdn;
        }

        return substr($msisdn, 0, 4).'***'.substr($msisdn, -3);
    }
}
