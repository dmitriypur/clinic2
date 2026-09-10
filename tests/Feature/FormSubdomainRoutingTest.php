<?php

namespace Tests\Feature;

use App\Http\Middleware\RedirectFormSubdomainRequests;
use Illuminate\Http\Request;
use Tests\TestCase;

class FormSubdomainRoutingTest extends TestCase
{
    public function test_non_root_form_subdomain_path_redirects_to_the_same_main_site_path(): void
    {
        config(['app.url' => 'https://zrenie.clinic']);

        $response = (new RedirectFormSubdomainRequests())->handle(
            Request::create('https://form.zrenie.clinic/kirov/ochki-s-linzami-stellest?source=form'),
            fn () => response('next'),
        );

        $this->assertSame(301, $response->getStatusCode());
        $this->assertSame(
            'https://zrenie.clinic/kirov/ochki-s-linzami-stellest?source=form',
            $response->headers->get('Location'),
        );
    }
}
