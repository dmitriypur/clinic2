<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Support\DoctorAge;
use PHPUnit\Framework\TestCase;

class DoctorAgeTest extends TestCase
{
    /** @test */
    public function it_builds_default_display_from_structured_months(): void
    {
        $this->assertSame('Ведет прием с 0', DoctorAge::buildDisplay(0, null));
        $this->assertSame('Ведет прием с 1 месяца', DoctorAge::buildDisplay(1, null));
        $this->assertSame('Ведет прием с 3 лет до 17 лет', DoctorAge::buildDisplay(36, 204));
        $this->assertSame('Ведет прием до 17 лет', DoctorAge::buildDisplay(null, 204));
    }

    /** @test */
    public function it_renders_template_placeholders_from_structured_months(): void
    {
        $display = DoctorAge::buildDisplay(
            1,
            204,
            'Врач принимает детей от {min} до {max}'
        );

        $this->assertSame('Врач принимает детей от 1 месяца до 17 лет', $display);
    }

    /** @test */
    public function it_splits_and_converts_month_storage_for_admin_form(): void
    {
        $this->assertSame([
            'value' => 3,
            'unit' => 'years',
        ], DoctorAge::splitMonths(36));

        $this->assertSame([
            'value' => 1,
            'unit' => 'months',
        ], DoctorAge::splitMonths(1));

        $this->assertSame(36, DoctorAge::convertInputToMonths(3, 'years'));
        $this->assertSame(1, DoctorAge::convertInputToMonths(1, 'months'));
    }
}
