<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Bot service token
    |--------------------------------------------------------------------------
    |
    | Shared secret the Discord gateway bot process (bot/) presents on every
    | request to Laravel's /internal/* API. See openspec design.md Decision 1.
    |
    */
    'service_token' => env('BOT_SERVICE_TOKEN'),

    /*
    |--------------------------------------------------------------------------
    | Outbound action queue
    |--------------------------------------------------------------------------
    |
    | Queue name that Discord-facing jobs (posting/closing a giveaway message)
    | are pushed to. The bot process polls GET /internal/outbound-actions for
    | the resulting DiscordOutboundAction rows - it does not consume this
    | Laravel queue directly.
    |
    */
    'outbound_queue' => env('DISCORD_OUTBOUND_QUEUE', 'discord-outbound'),

    /*
    |--------------------------------------------------------------------------
    | Discord OAuth (dashboard login)
    |--------------------------------------------------------------------------
    */
    'client_id' => env('DISCORD_CLIENT_ID'),
    'client_secret' => env('DISCORD_CLIENT_SECRET'),
    'redirect' => env('DISCORD_REDIRECT_URI'),

];
