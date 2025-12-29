<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Controller Auto-Discovery Path
    |--------------------------------------------------------------------------
    |
    | Tentukan direktori di mana Controller CRUD Anda berada.
    | Package akan melakukan scanning otomatis pada folder ini untuk
    | mendaftarkan route.
    |
    | Default: app_path('Http/Controllers')
    |
    */
    'controllers_path' => app_path('Http/Controllers'),

    /*
    |--------------------------------------------------------------------------
    | Middleware
    |--------------------------------------------------------------------------
    |
    | Default: ['api']
    |
    */
    'middleware' => ['api'],

    /*
    |--------------------------------------------------------------------------
    | Prefix
    |--------------------------------------------------------------------------
    |
    | Default: 'api'
    |
    */
    'prefix' => 'api',
];
