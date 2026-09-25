<?php

namespace Tests\Unit\Services;

use App\Exceptions\BookingWidgetApiException;
use App\Services\BookingWidgetApiService;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Mockery;
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

    /** @test */
    public function it_excludes_request_and_response_values_from_api_error_diagnostics(): void
    {
        $birthDateMarker = 'birth-date-marker-1985-04-03';
        $responseMarker = 'patient-phone-token-response-marker';

        config()->set('zrenie-clinic.booking_api_base_url', 'https://booking.test/api/v1');

        Http::fake([
            'https://booking.test/api/v1/cities/42/doctors*' => Http::response([
                'message' => $responseMarker,
            ], 400),
        ]);

        try {
            app(BookingWidgetApiService::class)->getDoctorsByCity(42, $birthDateMarker);
            $this->fail('Expected booking API exception was not thrown.');
        } catch (BookingWidgetApiException $exception) {
            $context = $exception->context();

            $this->assertSame('/cities/42/doctors', $context['path']);
            $this->assertSame(400, $context['status']);
            $this->assertSame(1, $context['attempt']);
            $this->assertIsInt($context['duration_ms']);
            $this->assertArrayNotHasKey('base_url', $context);
            $this->assertArrayNotHasKey('query', $context);
            $this->assertArrayNotHasKey('body', $context);
            $this->assertStringNotContainsString($birthDateMarker, json_encode($context, JSON_THROW_ON_ERROR));
            $this->assertStringNotContainsString($responseMarker, json_encode($context, JSON_THROW_ON_ERROR));
        }

        Log::shouldHaveReceived('error')
            ->once()
            ->with('Booking widget API returned an unsuccessful response.', Mockery::on(
                function (array $context) use ($birthDateMarker, $responseMarker): bool {
                    $encodedContext = json_encode($context, JSON_THROW_ON_ERROR);

                    $this->assertSame('/cities/42/doctors', $context['path']);
                    $this->assertSame(400, $context['status']);
                    $this->assertSame(1, $context['attempt']);
                    $this->assertArrayNotHasKey('base_url', $context);
                    $this->assertArrayNotHasKey('query', $context);
                    $this->assertArrayNotHasKey('body', $context);
                    $this->assertStringNotContainsString($birthDateMarker, $encodedContext);
                    $this->assertStringNotContainsString($responseMarker, $encodedContext);

                    return true;
                }
            ));
    }

    /** @test */
    public function it_logs_only_the_exception_class_for_connection_failures(): void
    {
        $exceptionMessageMarker = 'patient-phone-and-api-token-exception-marker';

        config()->set('zrenie-clinic.booking_api_base_url', 'https://booking.test/api/v1');

        Http::fake(function () use ($exceptionMessageMarker): never {
            throw new ConnectionException($exceptionMessageMarker);
        });

        try {
            app(BookingWidgetApiService::class)->getCities();
            $this->fail('Expected booking API exception was not thrown.');
        } catch (BookingWidgetApiException $exception) {
            $context = $exception->context();

            $this->assertSame('/cities', $context['path']);
            $this->assertSame(3, $context['attempt']);
            $this->assertSame(ConnectionException::class, $context['exception_class']);
            $this->assertStringNotContainsString($exceptionMessageMarker, json_encode($context, JSON_THROW_ON_ERROR));
        }

        Log::shouldHaveReceived('error')
            ->once()
            ->with('Booking widget API request failed due to a connection error.', Mockery::on(
                function (array $context) use ($exceptionMessageMarker): bool {
                    $this->assertSame(ConnectionException::class, $context['exception_class']);
                    $this->assertArrayNotHasKey('exception', $context);
                    $this->assertStringNotContainsString(
                        $exceptionMessageMarker,
                        json_encode($context, JSON_THROW_ON_ERROR)
                    );

                    return true;
                }
            ));
    }
}
