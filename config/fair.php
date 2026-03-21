<?php
declare(strict_types=1);

use App\Utils\Config;

return [
    'repos' => Config::repoList(env('FAIR_REPOS', '[]')),
    'paths' => [
        'packages' => '/packages',
    ],
    'domains' => [
        'webdid' => env('FAIR_WEBDID_DOMAIN', null),
    ]
];
