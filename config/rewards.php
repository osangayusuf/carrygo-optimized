<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Daily Check-In
    |--------------------------------------------------------------------------
    */
    'checkin' => [
        'base_points' => 2,

        /**
         * Bonus points awarded at these streak milestone days (on top of base).
         *
         * @var array<int, int>
         */
        'streak_milestones' => [
            3 => 5,
            7 => 15,
            14 => 30,
            30 => 100,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Spin the Wheel
    |--------------------------------------------------------------------------
    */
    'spin_wheel' => [
        'free_spins_per_day' => 1,

        /**
         * Wheel segments. Probabilities must sum to 100.
         * Probabilities are server-side only — never exposed to the frontend.
         *
         * @var array<int, array{points: int, probability: int}>
         */
        'segments' => [
            ['points' => 1,  'probability' => 35],
            ['points' => 2,  'probability' => 30],
            ['points' => 5,  'probability' => 20],
            ['points' => 10, 'probability' => 10],
            ['points' => 20, 'probability' => 4],
            ['points' => 50, 'probability' => 1],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Achievements
    |--------------------------------------------------------------------------
    */
    'achievements' => [
        'first_bid' => [
            'label' => 'First Bid',
            'description' => 'Place your first bid',
            'icon' => 'gavel',
            'points' => 5,
            'target' => 1,
        ],
        'first_win' => [
            'label' => 'First Win',
            'description' => 'Win your first auction',
            'icon' => 'emoji_events',
            'points' => 5,
            'target' => 1,
        ],
        'explorer' => [
            'label' => 'Explorer',
            'description' => 'Bid in 5 different categories',
            'icon' => 'explore',
            'points' => 5,
            'target' => 5,
        ],
        'big_spender' => [
            'label' => 'Big Spender',
            'description' => 'Spend 500 points on bids total',
            'icon' => 'payments',
            'points' => 5,
            'target' => 500,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Weekly Leaderboard
    |--------------------------------------------------------------------------
    */
    'leaderboard' => [
        /**
         * Points awarded to top bidders at end of week.
         * Key = rank (1-indexed), value = bonus points.
         *
         * @var array<int, int>
         */
        'weekly_bonuses' => [
            1 => 100,
            2 => 50,
            3 => 20,
        ],
    ],

];
