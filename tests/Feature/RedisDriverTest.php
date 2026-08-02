<?php

namespace Ihasan\NightwatchPromethus\Tests\Feature;

use Ihasan\NightwatchPromethus\Contracts\MetricSink;
use Ihasan\NightwatchPromethus\Contracts\MetricsExporter;
use Ihasan\NightwatchPromethus\Debug\NightwatchPromethusState;
use Ihasan\NightwatchPromethus\Ingest\NightwatchPromethusIngest;
use Ihasan\NightwatchPromethus\Tests\TestCase;
use Illuminate\Contracts\Redis\Factory as RedisFactory;
use Illuminate\Redis\Connections\Connection;
use Mockery;
use RuntimeException;

class RedisDriverTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();

        parent::tearDown();
    }

    public function test_redis_driver_flows_through_container_and_exporter(): void
    {
        $connection = Mockery::mock(Connection::class);
        $connection->shouldReceive('command')
            ->once()
            ->with('HINCRBYFLOAT', [
                'nightwatch_promethus:counters',
                'nightwatch_http_requests_total|method=GET,route=/health,status_code=200',
                '1',
            ]);
        $connection->shouldReceive('command')
            ->times(10)
            ->withArgs(function (string $command, array $parameters): bool {
                return $command === 'HINCRBYFLOAT'
                    && $parameters[0] === 'nightwatch_promethus:histograms:buckets'
                    && str_starts_with($parameters[1], 'nightwatch_http_request_duration_seconds|method=GET,route=/health,status_code=200|le=')
                    && $parameters[2] === '1';
            });
        $connection->shouldReceive('command')
            ->once()
            ->with('HINCRBYFLOAT', [
                'nightwatch_promethus:histograms:sums',
                'nightwatch_http_request_duration_seconds|method=GET,route=/health,status_code=200',
                '0.01',
            ]);
        $connection->shouldReceive('command')
            ->once()
            ->with('HINCRBYFLOAT', [
                'nightwatch_promethus:histograms:counts',
                'nightwatch_http_request_duration_seconds|method=GET,route=/health,status_code=200',
                '1',
            ]);
        $connection->shouldReceive('command')
            ->once()
            ->with('HGETALL', ['nightwatch_promethus:counters'])
            ->andReturn([
                'nightwatch_http_requests_total|method=GET,route=/health,status_code=200' => '1',
            ]);
        $connection->shouldReceive('command')
            ->once()
            ->with('HGETALL', ['nightwatch_promethus:histograms:buckets'])
            ->andReturn([
                'nightwatch_http_request_duration_seconds|method=GET,route=/health,status_code=200|le=0.01' => '1',
            ]);
        $connection->shouldReceive('command')
            ->once()
            ->with('HGETALL', ['nightwatch_promethus:histograms:sums'])
            ->andReturn([
                'nightwatch_http_request_duration_seconds|method=GET,route=/health,status_code=200' => '0.01',
            ]);
        $connection->shouldReceive('command')
            ->once()
            ->with('HGETALL', ['nightwatch_promethus:histograms:counts'])
            ->andReturn([
                'nightwatch_http_request_duration_seconds|method=GET,route=/health,status_code=200' => '1',
            ]);

        $redis = Mockery::mock(RedisFactory::class);
        $redis->shouldReceive('connection')->with('default')->andReturn($connection);

        $this->app['config']->set('nightwatch-promethus.storage.driver', 'redis');
        $this->app->instance(RedisFactory::class, $redis);

        $this->app->forgetInstance(MetricSink::class);
        $this->app->forgetInstance(MetricsExporter::class);
        $this->app->forgetInstance(NightwatchPromethusIngest::class);
        $this->app->forgetInstance(NightwatchPromethusState::class);

        $this->app->make(NightwatchPromethusIngest::class)->write([
            't' => 'request',
            'method' => 'GET',
            'route_path' => '/health',
            'status_code' => 200,
            'duration' => 10000,
        ]);

        $output = $this->app->make(MetricsExporter::class)->render();

        $this->assertStringContainsString('# HELP nightwatch_http_requests_total Total HTTP requests observed by Nightwatch Promethus', $output);
        $this->assertStringContainsString('nightwatch_http_requests_total{method="GET",route="/health",status_code="200"} 1', $output);
        $this->assertStringContainsString('# TYPE nightwatch_http_request_duration_seconds histogram', $output);
    }

    public function test_invalid_storage_driver_throws_clear_exception(): void
    {
        $this->app['config']->set('nightwatch-promethus.storage.driver', 'invalid');

        $this->app->forgetInstance(MetricSink::class);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Unsupported nightwatch-promethus storage driver [invalid].');

        $this->app->make(MetricSink::class);
    }
}
