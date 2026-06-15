<?php

use App\Modules\Bpjs\Providers\BpjsServiceProvider;
use App\Providers\AppServiceProvider;
use App\Providers\FortifyServiceProvider;

return [
    AppServiceProvider::class,
    BpjsServiceProvider::class,
    FortifyServiceProvider::class,
];
