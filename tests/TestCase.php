<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\DB;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;

    protected function setUp(): void
    {
        parent::setUp();

        // Safety check: Ensure we are running tests on sqlite :memory: or a dedicated test DB
        // to prevent accidental wiping of production/development databases.
        $connection = DB::getDefaultConnection();
        $database = config("database.connections.{$connection}.database");

        if ($connection !== 'sqlite' || $database !== ':memory:') {
             // You can uncomment this if you want to strictly enforce sqlite memory only
             // But sometimes people use a separate testing MySQL DB. 
             // For now, let's just ensure we are in 'testing' environment.
        }

        if (app()->environment() !== 'testing') {
            throw new \RuntimeException('Tests must be run in the "testing" environment. Current environment: ' . app()->environment());
        }
    }
}
