<?php

namespace Ihasan\NightwatchPromethus;

use Ihasan\NightwatchPromethus\Contracts\MetricSink;
use Ihasan\NightwatchPromethus\Debug\NightwatchPromethusState;
use Ihasan\NightwatchPromethus\Ingest\NightwatchPromethusIngest;
use Ihasan\NightwatchPromethus\Metrics\InMemoryMetricSink;
use Illuminate\Support\ServiceProvider;
use Laravel\Nightwatch\Core;
use Laravel\Nightwatch\Contracts\Ingest as NightwatchIngestContract;
use Laravel\Nightwatch\NightwatchServiceProvider as LaravelNightwatchServiceProvider;

class NightwatchPromethusServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__.'/../config/nightwatch-promethus.php',
            'nightwatch-promethus'
        );

        $this->app->singleton(MetricSink::class, function (): MetricSink {
            return new InMemoryMetricSink;
        });

        $this->app->singleton(NightwatchPromethusIngest::class, function (): NightwatchPromethusIngest {
            return new NightwatchPromethusIngest($this->app->make(MetricSink::class));
        });

        $this->app->singleton(NightwatchPromethusState::class, function (): NightwatchPromethusState {
            return new NightwatchPromethusState(
                $this->app->make(NightwatchPromethusIngest::class),
                $this->app->make(MetricSink::class),
            );
        });

        $this->app->alias(NightwatchPromethusState::class, 'nightwatch-promethus.state');
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

            $core = $this->app->make(Core::class);
            $ingest = $this->app->make(NightwatchPromethusIngest::class);

            $core->ingest = $ingest;

            $this->app->instance(NightwatchIngestContract::class, $ingest);
            $this->app->instance('nightwatch-promethus.ingest-replaced', true);

        });
    }
}
