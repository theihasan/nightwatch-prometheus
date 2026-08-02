<?php

namespace Ihasan\NightwatchPromethus\Metrics;

use Ihasan\NightwatchPromethus\Contracts\MetricSink;

class InMemoryMetricSink implements MetricSink
{
    /**
     * @var array<string, float>
     */
    private array $counters = [];

    /**
     * @var array<string, array{buckets: array<string, float>, sum: float, count: float}>
     */
    private array $histograms = [];

    public function incrementCounter(string $name, array $labels = [], int|float $value = 1): void
    {
        $key = $this->counterKey($name, $labels);

        $this->counters[$key] = ($this->counters[$key] ?? 0.0) + $value;
    }

    public function observeHistogram(string $name, array $labels, int|float $value, array $buckets): void
    {
        $key = $this->counterKey($name, $labels);

        $this->histograms[$key] ??= [
            'buckets' => [],
            'sum' => 0.0,
            'count' => 0.0,
        ];

        foreach ($buckets as $bucket) {
            $bucketKey = $this->normalizeBucket($bucket);

            $this->histograms[$key]['buckets'][$bucketKey] ??= 0.0;

            if ((float) $value <= $bucket) {
                $this->histograms[$key]['buckets'][$bucketKey] += 1.0;
            }
        }

        $this->histograms[$key]['sum'] += (float) $value;
        $this->histograms[$key]['count'] += 1.0;
    }

    /**
     * @return array<string, float>
     */
    public function counters(): array
    {
        return $this->counters;
    }

    public function histograms(): array
    {
        return $this->histograms;
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

    private function normalizeBucket(float $bucket): string
    {
        return rtrim(rtrim(sprintf('%.15F', $bucket), '0'), '.');
    }
}
