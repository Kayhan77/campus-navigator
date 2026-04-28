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

    /*
    |--------------------------------------------------------------------------
    | Minimum Keyword Length
    |--------------------------------------------------------------------------
    | Tokenized keywords shorter than this are ignored in multi-word search.
    | This removes low-signal fragments while keeping meaningful terms.
    */
    'min_keyword_length' => (int) env('SEARCH_MIN_KEYWORD_LENGTH', 2),

    /*
    |--------------------------------------------------------------------------
    | Search Stop Words
    |--------------------------------------------------------------------------
    | Common words removed from keyword tokenization.
    */
    'stop_words' => [
        'of',
        'the',
        'and',
        'a',
        'an',
        'in',
        'on',
        'to',
        'for',
    ],

    /*
    |--------------------------------------------------------------------------
    | Relevance Weights
    |--------------------------------------------------------------------------
    | Weights used to score ranked search results.
    */
    'relevance_weights' => [
        'exact' => (int) env('SEARCH_WEIGHT_EXACT', 120),
        'starts_with' => (int) env('SEARCH_WEIGHT_STARTS_WITH', 80),
        'contains' => (int) env('SEARCH_WEIGHT_CONTAINS', 45),
        'keyword_exact' => (int) env('SEARCH_WEIGHT_KEYWORD_EXACT', 24),
        'keyword_starts_with' => (int) env('SEARCH_WEIGHT_KEYWORD_STARTS_WITH', 14),
        'keyword_contains' => (int) env('SEARCH_WEIGHT_KEYWORD_CONTAINS', 8),
        'medium_field_contains' => (int) env('SEARCH_WEIGHT_MEDIUM_FIELD_CONTAINS', 20),
        'low_field_contains' => (int) env('SEARCH_WEIGHT_LOW_FIELD_CONTAINS', 8),
    ],

    /*
    |--------------------------------------------------------------------------
    | Suggestions Limit
    |--------------------------------------------------------------------------
    | Number of autocomplete suggestions returned by /search/suggestions.
    */
    'suggestion_limit' => (int) env('SEARCH_SUGGESTION_LIMIT', 5),
];
