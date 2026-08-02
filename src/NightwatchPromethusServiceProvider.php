<?php

namespace Ihasan\NightwatchPromethus;

use Illuminate\Support\ServiceProvider;
use Laravel\Nightwatch\Core;
use Laravel\Nightwatch\NightwatchServiceProvider as LaravelNightwatchServiceProvider;

class NightwatchPromethusServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__.'/../config/nightwatch-promethus.php',
            'nightwatch-promethus'
        );
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/nightwatch-promethus.php' => config_path('nightwatch-promethus.php'),
            ], 'nightwatch-promethus-config');
        }

        $this->registerNightwatchIngestReplacementHook();
    }

    private function registerNightwatchIngestReplacementHook(): void
    {
        $this->app->booted(function (): void {
            if (! config('nightwatch-promethus.enabled', true)) {
                return;
            }

            if (! class_exists(LaravelNightwatchServiceProvider::class)) {
                return;
            }

            if (! $this->app->bound(Core::class)) {
                return;
            }

            $this->app->make(Core::class);

        });
    }
}
