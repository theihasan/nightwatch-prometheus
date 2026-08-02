<?php

namespace Ihasan\NightwatchPromethus\Tests\Feature;

use Ihasan\NightwatchPromethus\Tests\TestCase;
use Illuminate\Support\Facades\Route;

class RouteConfigurationTest extends TestCase
{
    public function test_metrics_route_uses_no_middleware_by_default(): void
    {
        $route = Route::getRoutes()->getByName('nightwatch-promethus.metrics');

        $this->assertNotNull($route);
        $this->assertSame([], $route->gatherMiddleware());
    }
}

class MetricsRouteDisabledTest extends TestCase
{
    protected function definePackageEnvironment($app): void
    {
        $app['config']->set('nightwatch-promethus.routes.metrics.enabled', false);
    }

    public function test_metrics_route_can_be_disabled(): void
    {
        $this->get('/metrics')->assertNotFound();
    }
}

class DebugRouteProductionDefaultTest extends TestCase
{
    protected function definePackageEnvironment($app): void
    {
        $app['config']->set('app.env', 'production');
    }

    public function test_debug_route_is_disabled_by_default_in_production(): void
    {
        $this->get('/nightwatch-promethus/debug')->assertNotFound();
    }
}

class CustomMetricsRouteConfigurationTest extends TestCase
{
    protected function definePackageEnvironment($app): void
    {
        $app['config']->set('nightwatch-promethus.routes.metrics.path', 'internal/metrics');
        $app['config']->set('nightwatch-promethus.routes.metrics.middleware', ['web']);
    }

    public function test_metrics_route_path_and_middleware_are_configurable(): void
    {
        $route = Route::getRoutes()->getByName('nightwatch-promethus.metrics');

        $this->assertNotNull($route);
        $this->assertContains('web', $route->gatherMiddleware());

        $this->get('/metrics')->assertNotFound();
        $this->get('/internal/metrics')->assertOk();
    }
}
