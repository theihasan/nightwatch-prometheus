<?php

return [
    'enabled' => env('NIGHTWATCH_PROMETHUS_ENABLED', true),

    'routes' => [
        'metrics' => [
            'enabled' => env('NIGHTWATCH_PROMETHUS_METRICS_ROUTE_ENABLED', true),
            'path' => env('NIGHTWATCH_PROMETHUS_METRICS_ROUTE_PATH', 'metrics'),
            'middleware' => array_values(array_filter(explode(',', (string) env('NIGHTWATCH_PROMETHUS_METRICS_ROUTE_MIDDLEWARE', '')))),
        ],
        'debug' => [
            'enabled' => env('NIGHTWATCH_PROMETHUS_DEBUG_ROUTE_ENABLED'),
            'path' => env('NIGHTWATCH_PROMETHUS_DEBUG_ROUTE_PATH', 'nightwatch-promethus/debug'),
            'middleware' => array_values(array_filter(explode(',', (string) env('NIGHTWATCH_PROMETHUS_DEBUG_ROUTE_MIDDLEWARE', 'web')))),
        ],
    ],

    'storage' => [
        'driver' => env('NIGHTWATCH_PROMETHUS_STORAGE_DRIVER', 'redis'),

        'redis' => [
            'connection' => env('NIGHTWATCH_PROMETHUS_REDIS_CONNECTION', 'default'),
            'prefix' => env('NIGHTWATCH_PROMETHUS_REDIS_PREFIX', 'nightwatch_promethus'),
        ],
    ],
];
