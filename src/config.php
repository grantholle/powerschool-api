<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Debugging
    |--------------------------------------------------------------------------
    |
    | With debugging enabled you can view responses in Ray.
    | app.debug must be true as well for this to be enabled
    |
    */
    'debug_with_ray' => (bool) env('POWERSCHOOL_DEBUG', true),

    /*
    |--------------------------------------------------------------------------
    | Cache key
    |--------------------------------------------------------------------------
    |
    | This is the key used to store authentication tokens in the cache.
    |
    */

    'cache_key' => env('POWERSCHOOL_CACHE_KEY', 'powerschool_token'),

    /*
    |--------------------------------------------------------------------------
    | Server Address
    |--------------------------------------------------------------------------
    |
    | The fully qualified host name (including https) or IP address of your PowerSchool instance
    |
    */

    'server_address' => env('POWERSCHOOL_ADDRESS'),

    /*
    |--------------------------------------------------------------------------
    | Client ID and Secret
    |--------------------------------------------------------------------------
    |
    | The values of the client ID and secret obtained by installing a plugin
    | with <oauth></oauth> in the plugin's plugin.xml manifest.
    |
    */

    'client_id' => env('POWERSCHOOL_CLIENT_ID'),

    'client_secret' => env('POWERSCHOOL_CLIENT_SECRET'),
];
