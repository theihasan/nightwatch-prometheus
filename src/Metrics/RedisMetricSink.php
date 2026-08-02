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

    public function observeHistogram(string $name, array $labels, int|float $value, array $buckets): void
    {
        $key = $this->counterKey($name, $labels);

        foreach ($buckets as $bucket) {
            if ((float) $value > $bucket) {
                continue;
            }

            $this->connection()->command('HINCRBYFLOAT', [
                $this->histogramBucketsKey(),
                $key.'|le='.$this->normalizeBucket($bucket),
                '1',
            ]);
        }

        $this->connection()->command('HINCRBYFLOAT', [
            $this->histogramSumsKey(),
            $key,
            (string) $value,
        ]);

        $this->connection()->command('HINCRBYFLOAT', [
            $this->histogramCountsKey(),
            $key,
            '1',
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

    public function histograms(): array
    {
        $bucketValues = $this->connection()->command('HGETALL', [$this->histogramBucketsKey()]);
        $sumValues = $this->connection()->command('HGETALL', [$this->histogramSumsKey()]);
        $countValues = $this->connection()->command('HGETALL', [$this->histogramCountsKey()]);

        $histograms = [];

        if (is_array($bucketValues)) {
            foreach ($bucketValues as $key => $value) {
                [$histogramKey, $bucket] = explode('|le=', (string) $key, 2);

                $histograms[$histogramKey] ??= ['buckets' => [], 'sum' => 0.0, 'count' => 0.0];
                $histograms[$histogramKey]['buckets'][$bucket] = (float) $value;
            }
        }

        if (is_array($sumValues)) {
            foreach ($sumValues as $key => $value) {
                $histograms[(string) $key] ??= ['buckets' => [], 'sum' => 0.0, 'count' => 0.0];
                $histograms[(string) $key]['sum'] = (float) $value;
            }
        }

        if (is_array($countValues)) {
            foreach ($countValues as $key => $value) {
                $histograms[(string) $key] ??= ['buckets' => [], 'sum' => 0.0, 'count' => 0.0];
                $histograms[(string) $key]['count'] = (float) $value;
            }
        }

        return $histograms;
    }

    private function countersKey(): string
    {
        return $this->prefix.':counters';
    }

    private function histogramBucketsKey(): string
    {
        return $this->prefix.':histograms:buckets';
    }

    private function histogramSumsKey(): string
    {
        return $this->prefix.':histograms:sums';
    }

    private function histogramCountsKey(): string
    {
        return $this->prefix.':histograms:counts';
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

    private function normalizeBucket(float $bucket): string
    {
        return rtrim(rtrim(sprintf('%.15F', $bucket), '0'), '.');
    }
}
