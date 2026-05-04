<?php

namespace Tests\Unit;

use App\Http\Middleware\UtmParameters;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;
use Tests\TestCase;

class UtmParametersTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Cookie::unqueue('zrenie_utm');
    }

    /** @test */
    public function it_stores_utm_parameters_from_query_in_session_and_cookie(): void
    {
        $session = app('session.store');
        $session->flush();
        $session->start();

        $request = Request::create('/?utm_source=yandex_direct&utm_medium=night&utm_term=детский%20офтальмолог', 'GET');
        $request->setLaravelSession($session);

        $response = (new UtmParameters())->handle($request, static fn () => response('ok'));

        $this->assertSame('ok', $response->getContent());
        $this->assertSame('yandex_direct', $session->get('utm_source'));
        $this->assertSame('night', $session->get('utm_medium'));
        $this->assertSame('детский офтальмолог', $session->get('utm_term'));

        $cookie = Cookie::queued('zrenie_utm');
        $this->assertNotNull($cookie);

        $payload = json_decode($cookie->getValue(), true);
        $this->assertSame('yandex_direct', $payload['utm_source']);
        $this->assertSame('детский офтальмолог', $payload['utm_term']);
    }

    /** @test */
    public function it_restores_utm_parameters_from_cookie_when_query_is_missing(): void
    {
        $session = app('session.store');
        $session->flush();
        $session->start();

        $request = Request::create('/doctors', 'GET');
        $request->cookies->set('zrenie_utm', json_encode([
            'utm_source' => 'yandex_direct',
            'utm_campaign' => '12345',
            'utm_content' => '67890',
            'utm_term' => 'проверка зрения',
        ], JSON_UNESCAPED_UNICODE));
        $request->setLaravelSession($session);

        (new UtmParameters())->handle($request, static fn () => response('ok'));

        $this->assertSame('yandex_direct', $session->get('utm_source'));
        $this->assertSame('12345', $session->get('utm_campaign'));
        $this->assertSame('67890', $session->get('utm_content'));
        $this->assertSame('проверка зрения', $session->get('utm_term'));
    }
}
