<?php

namespace Tests;

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Env;

abstract class TestCase extends BaseTestCase
{
    // Laravel 11 adds createApplication in BaseTestCase

    public function createApplication(): Application
    {
        // Reset the Env repository so .env.testing values can be loaded,
        // overriding any APP_ENV already set in the container environment.
        putenv('APP_ENV=testing');
        $_ENV['APP_ENV'] = 'testing';
        $_SERVER['APP_ENV'] = 'testing';
        Env::enablePutenv(); // clears the cached immutable repository

        $app = require Application::inferBasePath().'/bootstrap/app.php';

        $app->make(Kernel::class)->bootstrap();

        return $app;
    }
}
