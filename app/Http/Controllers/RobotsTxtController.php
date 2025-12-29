<?php

namespace App\Http\Controllers;

use App\Settings\SeoSettings;
use Illuminate\Http\Response;

class RobotsTxtController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(SeoSettings $settings): Response
    {
        return response($settings->robots_txt)->header('Content-Type', 'text/plain');
    }
}
