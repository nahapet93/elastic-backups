<?php

return [
    'elasticsearch' => [
        'scheme' => env('ELASTICSEARCH_SCHEME', 'http'),
        'username' => env('ELASTICSEARCH_USER'),
        'password' => env('ELASTICSEARCH_PASS'),
        'host' => env('ELASTICSEARCH_HOST', 'localhost'),
        'port' => env('ELASTICSEARCH_PORT', 9200),
        'query_limit' => (int)env('ELASTICSEARCH_QUERY_LIMIT', 1000),
    ],
];
