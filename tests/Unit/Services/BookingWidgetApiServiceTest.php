<?php

namespace Tests\Unit\Services;

use App\Exceptions\BookingWidgetApiException;
use App\Services\BookingWidgetApiService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class BookingWidgetApiServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();
        Http::preventStrayRequests();
        Log::spy();
    }

    /** @test */
    public function it_retries_transient_booking_api_failures_before_succeeding(): void
    {
        config()->set('zrenie-clinic.booking_api_base_url', 'https://booking.test/api/v1');

        Http::fake([
            'https://booking.test/api/v1/cities' => Http::sequence()
                ->push(['message' => 'temporary error'], 500)
                ->push(['data' => [['id' => 1, 'name' => 'Москва']]], 200),
        ]);

        $payload = app(BookingWidgetApiService::class)->getCities();

        $this->assertSame([['id' => 1, 'name' => 'Москва']], $payload['data']);
        Http::assertSentCount(2);
    }

    /** @test */
    public function it_throws_a_domain_exception_for_invalid_booking_api_configuration(): void
    {
        config()->set('zrenie-clinic.booking_api_base_url', '');

        $this->expectException(BookingWidgetApiException::class);
        $this->expectExceptionMessage('Booking widget API base URL is not configured correctly.');

        app(BookingWidgetApiService::class)->getCities();
    }
}
