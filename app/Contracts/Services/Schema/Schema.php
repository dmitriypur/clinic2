<?php

namespace App\Contracts\Services\Schema;

use App\Settings\GeneralSettings;
use Spatie\SchemaOrg\LocalBusiness;
use Spatie\SchemaOrg\MedicalOrganization;

interface Schema
{
    public function localBusiness(GeneralSettings $settings): LocalBusiness;
}
