<?php

namespace Ihasan\NightwatchPromethus;

use Ihasan\NightwatchPromethus\Contracts\MetricSink;
use Ihasan\NightwatchPromethus\Contracts\MetricsExporter;
use Ihasan\NightwatchPromethus\Debug\NightwatchPromethusState;
use Ihasan\NightwatchPromethus\Ingest\NightwatchPromethusIngest;
use Ihasan\NightwatchPromethus\Metrics\InMemoryMetricSink;
use Ihasan\NightwatchPromethus\Metrics\PrometheusTextExporter;
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

        $this->app->singleton(MetricsExporter::class, function (): MetricsExporter {
            return new PrometheusTextExporter($this->app->make(MetricSink::class));
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
        $this->app->alias(MetricsExporter::class, 'nightwatch-promethus.exporter');
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/nightwatch-promethus.php' => config_path('nightwatch-promethus.php'),
            ], 'nightwatch-promethus-config');
        }

        if (config('nightwatch-promethus.enabled', true)) {
            $this->loadRoutesFrom(__DIR__.'/../routes/debug.php');
            $this->loadRoutesFrom(__DIR__.'/../routes/metrics.php');
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
