<?php

namespace Ihasan\NightwatchPromethus\Metrics;

use Ihasan\NightwatchPromethus\Contracts\MetricSink;
use Ihasan\NightwatchPromethus\Contracts\MetricsExporter;

class PrometheusTextExporter implements MetricsExporter
{
    public function __construct(private MetricSink $metricSink)
    {
        //
    }

    public function render(): string
    {
        if (! $this->metricSink instanceof InMemoryMetricSink) {
            return '';
        }

        $lines = [];
        $groupedMetrics = [];

        foreach ($this->metricSink->counters() as $key => $value) {
            [$name, $labels] = $this->parseCounterKey($key);

            $groupedMetrics[$name][] = [
                'labels' => $labels,
                'value' => $value,
            ];
        }

        ksort($groupedMetrics);

        foreach ($groupedMetrics as $name => $samples) {
            $lines[] = '# HELP '.$name.' Exported by Nightwatch Promethus';
            $lines[] = '# TYPE '.$name.' counter';

            foreach ($samples as $sample) {
                $lines[] = $name.$this->formatLabels($sample['labels']).' '.$sample['value'];
            }

            $lines[] = '';
        }

        return rtrim(implode("\n", $lines))."\n";
    }

    /**
     * @return array{0: string, 1: array<string, string>}
     */
    private function parseCounterKey(string $key): array
    {
        $parts = explode('|', $key, 2);
        $name = $parts[0];

        if (! isset($parts[1]) || $parts[1] === '') {
            return [$name, []];
        }

        $labels = [];

        foreach (explode(',', $parts[1]) as $pair) {
            [$label, $value] = explode('=', $pair, 2);
            $labels[$label] = $value;
        }

        ksort($labels);

        return [$name, $labels];
    }

    /**
     * @param array<string, string> $labels
     */
    private function formatLabels(array $labels): string
    {
        if ($labels === []) {
            return '';
        }

        $parts = [];

        foreach ($labels as $label => $value) {
            $parts[] = $label.'="'.$this->escapeLabelValue($value).'"';
        }

        return '{'.implode(',', $parts).'}';
    }

    private function escapeLabelValue(string $value): string
    {
        return str_replace(
            ["\\", "\n", '"'],
            ["\\\\", "\\n", '\\"'],
            $value,
        );
    }
}
