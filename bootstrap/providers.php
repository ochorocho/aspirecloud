<?php

declare(strict_types=1);
use App\Providers\AppServiceProvider;
use App\Providers\ElasticsearchServiceProvider;
use App\Providers\FortifyServiceProvider;
use App\Providers\JetstreamServiceProvider;

return [
    AppServiceProvider::class,
    ElasticsearchServiceProvider::class,
    FortifyServiceProvider::class,
    JetstreamServiceProvider::class,
];
