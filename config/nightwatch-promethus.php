<?php

return [
    'enabled' => env('NIGHTWATCH_PROMETHUS_ENABLED', true),

    'storage' => [
        'driver' => env('NIGHTWATCH_PROMETHUS_STORAGE_DRIVER', 'redis'),

        'redis' => [
            'connection' => env('NIGHTWATCH_PROMETHUS_REDIS_CONNECTION', 'default'),
            'prefix' => env('NIGHTWATCH_PROMETHUS_REDIS_PREFIX', 'nightwatch_promethus'),
        ],
    ],
];
