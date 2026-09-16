<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\ActivePoint;
use App\Models\User;
use App\Models\UserAnalytics;
use App\Services\ReferralService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class MsisdnLoginController extends Controller
{
    public function __construct(private readonly ReferralService $referralService) {}

    public function show(Request $request, ?string $msisdn = null): Response|RedirectResponse
    {
        $referralCode = $request->query('ref');

        if (is_string($referralCode) && trim($referralCode) !== '') {
            $request->session()->put('pending_referral_code', strtoupper(trim($referralCode)));
        }

        $msisdnToLogin = $msisdn ?? $request->query('msisdn');

        if ($msisdnToLogin) {
            return $this->attemptLogin($msisdnToLogin);
        }

        return Inertia::render('Login');
    }

    public function login(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'msisdn' => ['required', 'string'],
        ]);

        return $this->attemptLogin($validated['msisdn']);
    }

    private function attemptLogin(string $msisdn): RedirectResponse
    {
        $normalizedMsisdn = $this->normalizeMsisdn($msisdn);

        $active = ActivePoint::query()
            ->where('msisdn', $normalizedMsisdn)
            ->first();

        if ($active === null) {
            return redirect()->route('login')
                ->withErrors([
                    'msisdn' => 'You are not an active subscriber, please dial *20790# to subscribe.',
                ])
                ->withInput(['msisdn' => $msisdn]);
        }

        $user = User::firstOrCreate(
            ['msisdn' => $normalizedMsisdn],
            [
                'role' => 'user',
            ],
        );

        if ($user->isSuspended()) {
            return redirect()->route('login')
                ->withErrors([
                    'msisdn' => 'Your account has been suspended. Please contact support.',
                ])
                ->withInput(['msisdn' => $msisdn]);
        }

        if (empty($user->referral_code)) {
            $user->update([
                'referral_code' => $this->generateUniqueReferralCode(),
            ]);
        }

        UserAnalytics::query()->create([
            'msisdn' => $normalizedMsisdn,
            'action' => 'login',
            'activity_date' => now(),
        ]);

        Auth::login($user);

        request()->session()->regenerate();

        $redirect = redirect()->intended(route('home'));

        $pendingReferralCode = request()->session()->pull('pending_referral_code');

        if (is_string($pendingReferralCode) && $pendingReferralCode !== '') {
            $result = $this->referralService->processAfterLogin($user, $pendingReferralCode);

            if ($result['processed']) {
                $redirect->with(
                    'success',
                    'Welcome! Your referral bonus has been added to your rewards wallet.'
                );
            }
        }

        return $redirect;
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home');
    }

    private function normalizeMsisdn(string $raw): string
    {
        $msisdn = trim($raw);

        return preg_replace('/^(0)/', '234', $msisdn);
    }

    private function generateUniqueReferralCode(): string
    {
        do {
            $code = strtoupper(str()->random(8));
        } while (User::where('referral_code', $code)->exists());

        return $code;
    }
}
