<?php

namespace Ihasan\NightwatchPromethus;

use Ihasan\NightwatchPromethus\Contracts\MetricSink;
use Ihasan\NightwatchPromethus\Contracts\MetricsExporter;
use Ihasan\NightwatchPromethus\Debug\NightwatchPromethusState;
use Ihasan\NightwatchPromethus\Ingest\NightwatchPromethusIngest;
use Ihasan\NightwatchPromethus\Metrics\InMemoryMetricSink;
use Ihasan\NightwatchPromethus\Metrics\MetricDefinitions;
use Ihasan\NightwatchPromethus\Metrics\PrometheusTextExporter;
use Ihasan\NightwatchPromethus\Metrics\RedisMetricSink;
use Illuminate\Contracts\Redis\Factory as RedisFactory;
use Illuminate\Support\ServiceProvider;
use Laravel\Nightwatch\Core;
use Laravel\Nightwatch\Contracts\Ingest as NightwatchIngestContract;
use Laravel\Nightwatch\NightwatchServiceProvider as LaravelNightwatchServiceProvider;
use RuntimeException;

class NightwatchPromethusServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__.'/../config/nightwatch-promethus.php',
            'nightwatch-promethus'
        );

        $this->app->singleton(MetricSink::class, function (): MetricSink {
            return $this->resolveMetricSink();
        });

        $this->app->singleton(MetricDefinitions::class, function (): MetricDefinitions {
            return new MetricDefinitions;
        });

        $this->app->singleton(MetricsExporter::class, function (): MetricsExporter {
            return new PrometheusTextExporter(
                $this->app->make(MetricSink::class),
                $this->app->make(MetricDefinitions::class),
            );
        });

        $this->app->singleton(NightwatchPromethusIngest::class, function (): NightwatchPromethusIngest {
            return new NightwatchPromethusIngest(
                $this->app->make(MetricSink::class),
                $this->app->make(MetricDefinitions::class),
            );
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
            $this->registerRoutes();
        }

        $this->registerNightwatchIngestReplacementHook();
    }

    private function registerRoutes(): void
    {
        if ($this->metricsRouteEnabled()) {
            $this->loadRoutesFrom(__DIR__.'/../routes/metrics.php');
        }

        if ($this->debugRouteEnabled()) {
            $this->loadRoutesFrom(__DIR__.'/../routes/debug.php');
        }
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

    private function resolveMetricSink(): MetricSink
    {
        return match (config('nightwatch-promethus.storage.driver', 'redis')) {
            'in_memory' => new InMemoryMetricSink,
            'redis' => new RedisMetricSink(
                $this->app->make(RedisFactory::class),
                config('nightwatch-promethus.storage.redis.connection', 'default'),
                config('nightwatch-promethus.storage.redis.prefix', 'nightwatch_promethus'),
            ),
            default => throw new RuntimeException('Unsupported nightwatch-promethus storage driver ['.config('nightwatch-promethus.storage.driver').'].'),
        };
    }

    private function metricsRouteEnabled(): bool
    {
        return (bool) config('nightwatch-promethus.routes.metrics.enabled', true);
    }

    private function debugRouteEnabled(): bool
    {
        $configured = config('nightwatch-promethus.routes.debug.enabled');

        if ($configured === null) {
            return ! $this->app->environment('production');
        }

        return (bool) $configured;
    }
}
