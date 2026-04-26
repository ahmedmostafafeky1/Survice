<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Lusha API Key
    |--------------------------------------------------------------------------
    | Your Lusha API key, available in the Lusha dashboard under
    | Account Settings → API Access.
    */
    'api_key' => env('LUSHA_API_KEY', ''),

    /*
    |--------------------------------------------------------------------------
    | Lusha API Base URL
    |--------------------------------------------------------------------------
    */
    'base_url' => env('LUSHA_BASE_URL', 'https://api.lusha.com'),

    /*
    |--------------------------------------------------------------------------
    | Request Timeout (seconds)
    |--------------------------------------------------------------------------
    */
    'timeout' => env('LUSHA_TIMEOUT', 30),

    /*
    |--------------------------------------------------------------------------
    | Default Prospecting Limit
    |--------------------------------------------------------------------------
    | Maximum number of leads returned per prospecting call.
    */
    'default_limit' => env('LUSHA_DEFAULT_LIMIT', 25),

];
