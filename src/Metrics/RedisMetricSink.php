<?php

namespace Ihasan\NightwatchPromethus\Metrics;

use Ihasan\NightwatchPromethus\Contracts\MetricSink;
use Illuminate\Contracts\Redis\Factory as RedisFactory;
use Illuminate\Redis\Connections\Connection;

class RedisMetricSink implements MetricSink
{
    public function __construct(
        private RedisFactory $redis,
        private string $connection = 'default',
        private string $prefix = 'nightwatch_promethus',
    ) {
        //
    }

    public function incrementCounter(string $name, array $labels = [], int|float $value = 1): void
    {
        $this->connection()->command('HINCRBYFLOAT', [
            $this->countersKey(),
            $this->counterKey($name, $labels),
            (string) $value,
        ]);
    }

    public function counters(): array
    {
        $values = $this->connection()->command('HGETALL', [$this->countersKey()]);

        if (! is_array($values)) {
            return [];
        }

        $counters = [];

        foreach ($values as $key => $value) {
            $counters[(string) $key] = (float) $value;
        }

        return $counters;
    }

    private function countersKey(): string
    {
        return $this->prefix.':counters';
    }

    /**
     * @param array<string, string> $labels
     */
    private function counterKey(string $name, array $labels): string
    {
        ksort($labels);

        if ($labels === []) {
            return $name;
        }

        $parts = [];

        foreach ($labels as $label => $value) {
            $parts[] = $label.'='.$value;
        }

        return $name.'|'.implode(',', $parts);
    }

    private function connection(): Connection
    {
        return $this->redis->connection($this->connection);
    }
}
