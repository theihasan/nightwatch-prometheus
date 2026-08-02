<?php

namespace Ihasan\NightwatchPromethus\Contracts;

interface MetricSink
{
    /**
     * @param array<string, string> $labels
     */
    public function incrementCounter(string $name, array $labels = [], int|float $value = 1): void;

    /**
     * @param array<string, string> $labels
     * @param list<float> $buckets
     */
    public function observeHistogram(string $name, array $labels, int|float $value, array $buckets): void;

    /**
     * @return array<string, float>
     */
    public function counters(): array;

    /**
     * @return array<string, array{buckets: array<string, float>, sum: float, count: float}>
     */
    public function histograms(): array;
}
