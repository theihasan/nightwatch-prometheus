<?php

namespace Ihasan\NightwatchPromethus\Tests;

use Ihasan\NightwatchPromethus\NightwatchPromethusServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

abstract class TestCase extends Orchestra
{
    protected function getPackageProviders($app): array
    {
        return [
            NightwatchPromethusServiceProvider::class,
        ];
    }
}
