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
        'bid_id' => env('WINNER_POPUP_BID_ID', 799),
    ],

    /*
    |--------------------------------------------------------------------------
    | Event Popup
    |--------------------------------------------------------------------------
    |
    | If enabled, the homepage will show a promotional popup for configured
    | event bids. Each item has a bid_id and display title. Item names come
    | from the database. Set enabled to false to disable.
    |
    */
    'event_popup' => [
        'enabled' => env('EVENT_POPUP_ENABLED', true),
        'items' => [
            ['bid_id' => 761, 'title' => 'Bid Aura'],
            ['bid_id' => 798, 'title' => 'Stylish Aura'],
        ],
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
