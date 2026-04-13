<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ProfileController extends Controller
{
    public function show(Request $request): Response
    {
        $user = $request->user();

        return Inertia::render('Profile', [
            'user' => [
                'msisdn' => $user->msisdn,
                'referral_code' => $user->referral_code,
                'points' => $user->activePoint?->points ?? 0,
            ],
            'referral_url' => route('home', ['ref' => $user->referral_code]),
        ]);
    }
}
