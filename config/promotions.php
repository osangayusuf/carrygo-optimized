<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Winner Popup
    |--------------------------------------------------------------------------
    |
    | If enabled, the homepage will show a once-per-session congratulations
    | popup for the most recent winner of the configured bid ID.
    | Set WINNER_POPUP_BID_ID to null (or omit the env var) to disable.
    |
    */
    'winner_popup' => [
        'enabled' => env('WINNER_POPUP_ENABLED', true),
        'bid_id' => env('WINNER_POPUP_BID_ID', 433),
    ],

    /*
    |--------------------------------------------------------------------------
    | Notifications
    |--------------------------------------------------------------------------
    |
    | Promotional notifications displayed in the navbar notification overlay.
    | Each entry must have: id, title, body, icon (Material Symbol), created_at.
    | Optional: action_url, action_label
    |
    */
    'notifications' => [
        [
            'id' => 1,
            'title' => 'Weekly Leaderboard Resets Soon',
            'body' => 'The weekly leaderboard resets every Monday at midnight. Place your bids now to climb the ranks!',
            'icon' => 'emoji_events',
            'created_at' => '2026-04-12',
            'action_route' => 'tasks',
            'action_label' => 'Check Leaderboard',
        ],
        [
            'id' => 2,
            'title' => 'Earn Bonus Spin Points Today',
            'body' => 'Complete your daily tasks to earn extra spin points. Unused spins expire at end of day.',
            'icon' => 'stars',
            'created_at' => '2026-04-11',
            'action_route' => 'tasks',
            'action_label' => 'View Tasks',
        ],
    ],

];
