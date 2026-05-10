<?php

namespace Tests\Unit;

use App\Clinic;
use Tests\TestCase;

class ClinicScriptVariablesUtmTest extends TestCase
{
    /** @test */
    public function it_exposes_default_site_marker_when_no_utm_parameters_are_available(): void
    {
        $this->get('/');

        $this->assertSame(['default_site' => 'organic'], Clinic::frontendUtmParameters());
    }

    /** @test */
    public function it_exposes_real_utm_parameters_without_adding_organic_marker(): void
    {
        $this->get('/?utm_source=yandex_direct&utm_medium=night');

        $this->assertSame([
            'utm_source' => 'yandex_direct',
            'utm_medium' => 'night',
        ], Clinic::frontendUtmParameters());
    }
}
