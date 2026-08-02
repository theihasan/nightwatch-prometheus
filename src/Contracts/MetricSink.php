<?php

namespace Ihasan\NightwatchPromethus\Contracts;

interface MetricSink
{
    /**
     * @param array<string, string> $labels
     */
    public function incrementCounter(string $name, array $labels = [], int|float $value = 1): void;

    /**
     * @return array<string, float>
     */
    public function counters(): array;
}
