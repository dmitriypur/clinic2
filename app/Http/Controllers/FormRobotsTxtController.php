<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class FormRobotsTxtController extends Controller
{
    public function __invoke()
    {
        return response("User-agent: *\nDisallow: /", 200)
            ->header('Content-Type', 'text/plain');
    }
}
