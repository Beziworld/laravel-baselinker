<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Default connection
    |--------------------------------------------------------------------------
    |
    | Connection used by the BaseLinker facade and by the injected client.
    |
    */

    'default' => env('BASELINKER_CONNECTION', 'default'),

    /*
    |--------------------------------------------------------------------------
    | Connections (accounts)
    |--------------------------------------------------------------------------
    |
    | One entry per BaseLinker account. The API token is generated in the
    | panel under "Account & other > My account > API". Options left empty
    | here fall back to the shared options below. Tokens stored per tenant
    | (for example in the database) do not need an entry: use
    | BaseLinker::using($token) instead.
    |
    */

    'connections' => [
        'default' => [
            'token' => env('BASELINKER_TOKEN'),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Shared options
    |--------------------------------------------------------------------------
    */

    'url' => env('BASELINKER_URL', 'https://api.baselinker.com/connector.php'),

    'timeout' => (int) env('BASELINKER_TIMEOUT', 30),

    'connect_timeout' => (int) env('BASELINKER_CONNECT_TIMEOUT', 10),

    /*
    |--------------------------------------------------------------------------
    | Rate limiting
    |--------------------------------------------------------------------------
    |
    | BaseLinker allows 100 requests per minute. The client-side limiter keeps
    | a counter per token in the cache (use a shared store such as Redis when
    | several servers or queue workers use the same token). When the budget
    | is used up the client either waits for the window to reset ("wait",
    | at most max_wait seconds) or throws RateLimitExceeded ("throw").
    | Requests are never retried automatically.
    |
    */

    'rate_limit' => [
        'enabled' => (bool) env('BASELINKER_RATE_LIMIT', true),
        'max_attempts' => (int) env('BASELINKER_RATE_LIMIT_MAX', 100),
        'decay_seconds' => 60,
        'strategy' => env('BASELINKER_RATE_LIMIT_STRATEGY', 'wait'),
        'max_wait' => (int) env('BASELINKER_RATE_LIMIT_MAX_WAIT', 60),
        'cache_store' => env('BASELINKER_CACHE_STORE'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Journal polling
    |--------------------------------------------------------------------------
    |
    | The baselinker:poll command reads getJournalList (order events) and,
    | when enabled, getOrderReturnJournalList (return events), dispatching one
    | Laravel event per entry. The last processed log id of every connection
    | is kept in the cache store below, so use a persistent store. The journal
    | must be enabled in the account API settings, otherwise BaseLinker
    | returns an empty list. Entries are kept by BaseLinker for 3 days only.
    |
    */

    'journal' => [
        'poll' => ['orders'],
        'order_event_types' => [],
        'return_event_types' => [],
        'max_pages' => (int) env('BASELINKER_JOURNAL_MAX_PAGES', 50),
        'cache_store' => env('BASELINKER_JOURNAL_CACHE_STORE'),
        'cache_prefix' => 'baselinker:journal',
    ],

];
