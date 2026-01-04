<?php

use PhpJunior\Glosa\Http\Middleware\Authorize;

return [
    /*
    |--------------------------------------------------------------------------
    | Route Prefix
    |--------------------------------------------------------------------------
    |
    | This prefix will be used for all of the routes registered by the
    | package. You can change this to whatever you want.
    |
    */
    'route_prefix' => 'glosa',

    /*
    |--------------------------------------------------------------------------
    | Middleware
    |--------------------------------------------------------------------------
    |
    | These middleware will be assigned to every route within the package.
    |
    */
    'middleware' => [
        'web',
        Authorize::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | Public API
    |--------------------------------------------------------------------------
    |
    | If enabled, a public endpoint will be available to fetch translations
    | for a specific locale without authentication.
    | URL: /api/translations/{locale}
    |
    */
    'enable_public_api' => env('GLOSA_ENABLE_PUBLIC_API', true),

    /*
    |--------------------------------------------------------------------------
    | Public API URL
    |--------------------------------------------------------------------------
    |
    | The URL pattern for the public translations endpoint.
    | The {locale} parameter is required.
    |
    */
    'public_api_url' => env('GLOSA_PUBLIC_API_URL', 'api/translations/{locale}'),

    /*
    |--------------------------------------------------------------------------
    | Public API Response Format
    |--------------------------------------------------------------------------
    |
    | Maintain the dot notation or nest the response.
    | true: Nested (e.g., ['messages' => ['welcome' => 'Hello']])
    | false: Dot notation (e.g., ['messages.welcome' => 'Hello'])
    |
    */
    'public_api_nested' => env('GLOSA_PUBLIC_API_NESTED', true),

    /*
    |--------------------------------------------------------------------------
    | Database Translation Loading
    |--------------------------------------------------------------------------
    |
    | If enabled, the package will automatically load translations from the
    | database when using Laravel's translation functions like __('key').
    |
    */
    'enable_db_loading' => env('GLOSA_ENABLE_DB_LOADING', true),
];
