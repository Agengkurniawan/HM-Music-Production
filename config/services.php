<?php

return [

    'ai_search' => [
        'enabled' => env('AI_SEARCH_ENABLED', false),
        'api_key' => env('GEMINI_API_KEY'),
        'model' => env('AI_SEARCH_MODEL', 'gemini-embedding-2'),
        'dimensions' => env('AI_SEARCH_DIMENSIONS', 768),
        'top_k' => env('AI_SEARCH_TOP_K', 5),
        'min_similarity' => env('AI_SEARCH_MIN_SIMILARITY', 0.20),
        'connect_timeout' => env('AI_SEARCH_CONNECT_TIMEOUT', 3),
        'timeout' => env('AI_SEARCH_TIMEOUT', 10),
        'lyrics_grounding_enabled' => env('AI_SEARCH_LYRICS_GROUNDING_ENABLED', true),
        'lyrics_cache_days' => env('AI_SEARCH_LYRICS_CACHE_DAYS', 30),
        'query_models' => array_values(array_filter(array_map(
            'trim',
            explode(',', env('AI_SEARCH_QUERY_MODELS', 'gemini-3.6-flash,gemini-3.5-flash-lite'))
        ))),
        'known_lyric_signatures' => [
            'wajar bila saat ini ku iri pada kalian' => ['song_title' => 'Diary Depresiku', 'artist' => 'Last Child'],
            'kuingat saat ayah pergi' => ['song_title' => 'Diary Depresiku', 'artist' => 'Last Child'],
            'ku ingat saat ayah pergi' => ['song_title' => 'Diary Depresiku', 'artist' => 'Last Child'],
            'wis sak mestine ati iki nelangsa' => ['song_title' => 'Cidro', 'artist' => 'Didi Kempot'],
            'opo ora eling naliko semana' => ['song_title' => 'Cidro', 'artist' => 'Didi Kempot'],
            'kepiye maneh iki pancen nasibku' => ['song_title' => 'Cidro', 'artist' => 'Didi Kempot'],
            'remok ati iki yen eling janjine' => ['song_title' => 'Cidro', 'artist' => 'Didi Kempot'],
            'ora ngiro jebulmu lamis wae' => ['song_title' => 'Cidro', 'artist' => 'Didi Kempot'],
            'opo koe ra ngerti larane' => ['song_title' => 'Sanes', 'artist' => 'Guyon Waton feat. Denny Caknan'],
            'adheme angin wengi teka' => ['song_title' => 'Wirang', 'artist' => 'Guyon Waton'],
            'ademe angin wengi teka' => ['song_title' => 'Wirang', 'artist' => 'Guyon Waton'],
            'kedaden tenan nduwe omah sing ra berisik' => ['song_title' => 'Sinarengan', 'artist' => 'Denny Caknan feat. Bella Bonita'],
            'kebak katresnan kebak kasih lan sayang' => ['song_title' => 'Sinarengan', 'artist' => 'Denny Caknan feat. Bella Bonita'],
            'ngobrol ra ana enteke ning tengah wengi' => ['song_title' => 'Sinarengan', 'artist' => 'Denny Caknan feat. Bella Bonita'],
            'ngobrol raono entek e ning tengah wengi' => ['song_title' => 'Sinarengan', 'artist' => 'Denny Caknan feat. Bella Bonita'],
            'tetes embun sing ngancani' => ['song_title' => 'Sinarengan', 'artist' => 'Denny Caknan feat. Bella Bonita'],
            'matur suwun wis ngancani aku selama iki' => ['song_title' => 'Sinarengan', 'artist' => 'Denny Caknan feat. Bella Bonita'],
            'wis isa saling nguwat nguwatke' => ['song_title' => 'Sinarengan', 'artist' => 'Denny Caknan feat. Bella Bonita'],
            'hubungan iki berlanjut' => ['song_title' => 'Sinarengan', 'artist' => 'Denny Caknan feat. Bella Bonita'],
        ],
    ],

    'tavily' => [
        'enabled' => env('TAVILY_SEARCH_ENABLED', true),
        'api_key' => env('TAVILY_API_KEY'),
        'timeout' => env('TAVILY_SEARCH_TIMEOUT', 12),
        'cache_days' => env('TAVILY_LYRICS_CACHE_DAYS', 30),
    ],

    'ai_enrichment' => [
        'enabled' => env('AI_ENRICHMENT_ENABLED', false),
        'model' => env('AI_ENRICHMENT_MODEL', 'gemini-3.6-flash'),
        'grounding_enabled' => env('AI_ENRICHMENT_GROUNDING_ENABLED', false),
        'connect_timeout' => env('AI_ENRICHMENT_CONNECT_TIMEOUT', 3),
        'timeout' => env('AI_ENRICHMENT_TIMEOUT', 60),
    ],

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'google' => [
        'client_id' => env('GOOGLE_CLIENT_ID'),
        'client_secret' => env('GOOGLE_CLIENT_SECRET'),
        'redirect' => env('GOOGLE_REDIRECT_URI'),
    ],

    'facebook' => [
        'client_id' => env('FACEBOOK_CLIENT_ID'),
        'client_secret' => env('FACEBOOK_CLIENT_SECRET'),
        'redirect' => env('FACEBOOK_REDIRECT_URI'),
    ],

    'midtrans' => [
        'server_key' => env('MIDTRANS_SERVER_KEY'),
        'client_key' => env('MIDTRANS_CLIENT_KEY'),
        'merchant_id' => env('MIDTRANS_MERCHANT_ID'),
        'is_production' => env('MIDTRANS_IS_PRODUCTION'),
    ],

];
