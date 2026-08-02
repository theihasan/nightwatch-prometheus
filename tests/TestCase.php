<?php

namespace Ihasan\NightwatchPromethus\Tests;

use Ihasan\NightwatchPromethus\NightwatchPromethusServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

abstract class TestCase extends Orchestra
{
    protected function getEnvironmentSetUp($app): void
    {
        $app['config']->set('app.key', 'base64:AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA=');
        $app['config']->set('nightwatch-promethus.storage.driver', 'in_memory');

        $this->definePackageEnvironment($app);
    }

    protected function definePackageEnvironment($app): void
    {
        //
    }

    protected function getPackageProviders($app): array
    {
        return [
            NightwatchPromethusServiceProvider::class,
        ];
    }
}
