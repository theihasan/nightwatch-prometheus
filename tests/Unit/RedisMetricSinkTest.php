<?php

namespace Ihasan\NightwatchPromethus\Tests\Unit;

use Ihasan\NightwatchPromethus\Metrics\RedisMetricSink;
use Illuminate\Contracts\Redis\Factory as RedisFactory;
use Illuminate\Redis\Connections\Connection;
use Mockery;
use PHPUnit\Framework\TestCase;

class RedisMetricSinkTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();

        parent::tearDown();
    }

    public function test_increment_counter_writes_to_redis_hash(): void
    {
        $connection = Mockery::mock(Connection::class);
        $connection->shouldReceive('command')
            ->once()
            ->with('HINCRBYFLOAT', [
                'nightwatch_promethus:counters',
                'nightwatch_http_requests_total|method=GET,route=/health,status_code=200',
                '1',
            ]);

        $redis = Mockery::mock(RedisFactory::class);
        $redis->shouldReceive('connection')->once()->with('metrics')->andReturn($connection);

        $sink = new RedisMetricSink($redis, 'metrics', 'nightwatch_promethus');

        $sink->incrementCounter('nightwatch_http_requests_total', [
            'status_code' => '200',
            'route' => '/health',
            'method' => 'GET',
        ]);

        $this->addToAssertionCount(1);
    }

    public function test_counters_reads_float_values_from_redis_hash(): void
    {
        $connection = Mockery::mock(Connection::class);
        $connection->shouldReceive('command')
            ->once()
            ->with('HGETALL', ['nightwatch_promethus:counters'])
            ->andReturn([
                'nightwatch_http_requests_total|method=GET,route=/health,status_code=200' => '2',
            ]);

        $redis = Mockery::mock(RedisFactory::class);
        $redis->shouldReceive('connection')->once()->with('metrics')->andReturn($connection);

        $sink = new RedisMetricSink($redis, 'metrics', 'nightwatch_promethus');

        $this->assertSame([
            'nightwatch_http_requests_total|method=GET,route=/health,status_code=200' => 2.0,
        ], $sink->counters());
    }
}
