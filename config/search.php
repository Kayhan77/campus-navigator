<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Search Result Cache TTL
    |--------------------------------------------------------------------------
    | Number of seconds search results are cached in Redis.
    | Set to 0 to disable caching.
    */
    'cache_ttl' => (int) env('SEARCH_CACHE_TTL', 300),

    /*
    |--------------------------------------------------------------------------
    | Maximum per_page Limit
    |--------------------------------------------------------------------------
    | Hard ceiling on how many records a single page request can return.
    | Prevents memory exhaustion from oversized requests.
    */
    'max_per_page' => (int) env('SEARCH_MAX_PER_PAGE', 50),

    /*
    |--------------------------------------------------------------------------
    | Default per_page
    |--------------------------------------------------------------------------
    */
    'default_per_page' => (int) env('SEARCH_DEFAULT_PER_PAGE', 15),

    /*
    |--------------------------------------------------------------------------
    | Cache Tag Prefix
    |--------------------------------------------------------------------------
    | Prefix applied to every Redis cache tag so keys never collide
    | with other application tags.
    */
    'cache_tag_prefix' => env('SEARCH_CACHE_TAG_PREFIX', 'search'),

    /*
    |--------------------------------------------------------------------------
    | Minimum Search Query Length
    |--------------------------------------------------------------------------
    | Ignore search terms shorter than this to avoid full-table scans on
    | common noise words (e.g. "a", "to").
    */
    'min_search_length' => (int) env('SEARCH_MIN_LENGTH', 2),

    /*
    |--------------------------------------------------------------------------
    | Maximum Search Query Length
    |--------------------------------------------------------------------------
    | Clamp the incoming search term to this length before querying.
    | Prevents abuse via enormous LIKE patterns that saturate the DB.
    | 100 characters is enough for any realistic search; adjust as needed.
    */
    'max_search_length' => (int) env('SEARCH_MAX_LENGTH', 100),

    /*
    |--------------------------------------------------------------------------
    | Admin No-Cache Bypass
    |--------------------------------------------------------------------------
    | When true, admins can pass ?no_cache=1 to skip the cache.
    */
    'admin_no_cache' => (bool) env('SEARCH_ADMIN_NO_CACHE', true),

];
