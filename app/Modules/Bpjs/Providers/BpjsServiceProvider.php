<?php

namespace App\Modules\Bpjs\Providers;

use App\Modules\Bpjs\Contracts\AntrolTaskIdGateway;
use App\Modules\Bpjs\Contracts\BpjsIntegrationSettings;
use App\Modules\Bpjs\Contracts\BpjsTaskIdLogRepository;
use App\Modules\Bpjs\Infrastructure\DatabaseBpjsIntegrationSettings;
use App\Modules\Bpjs\Infrastructure\DatabaseTaskIdLogRepository;
use App\Modules\Bpjs\Services\AntrolTaskIdService;
use Illuminate\Support\ServiceProvider;

class BpjsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(BpjsTaskIdLogRepository::class, DatabaseTaskIdLogRepository::class);
        $this->app->singleton(BpjsIntegrationSettings::class, DatabaseBpjsIntegrationSettings::class);
        $this->app->singleton(AntrolTaskIdGateway::class, AntrolTaskIdService::class);
    }
}
