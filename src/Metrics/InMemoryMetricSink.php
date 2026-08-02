<?php

namespace Ihasan\NightwatchPromethus\Metrics;

use Ihasan\NightwatchPromethus\Contracts\MetricSink;

class InMemoryMetricSink implements MetricSink
{
    /**
     * @var array<string, float>
     */
    private array $counters = [];

    public function incrementCounter(string $name, array $labels = [], int|float $value = 1): void
    {
        $key = $this->counterKey($name, $labels);

        $this->counters[$key] = ($this->counters[$key] ?? 0.0) + $value;
    }

    /**
     * @return array<string, float>
     */
    public function counters(): array
    {
        return $this->counters;
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
}
