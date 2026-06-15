<?php

namespace App\Modules\Bpjs\Infrastructure;

use App\Models\Settings\AppSetting;
use App\Modules\Bpjs\Contracts\BpjsIntegrationSettings;

class DatabaseBpjsIntegrationSettings implements BpjsIntegrationSettings
{
    public function antrolTaskIdEnabled(): bool
    {
        return AppSetting::bpjsAntrolTaskIdEnabled();
    }
}
