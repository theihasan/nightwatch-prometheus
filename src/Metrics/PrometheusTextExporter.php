<?php

namespace Ihasan\NightwatchPromethus\Metrics;

use Ihasan\NightwatchPromethus\Contracts\MetricSink;
use Ihasan\NightwatchPromethus\Contracts\MetricsExporter;

class PrometheusTextExporter implements MetricsExporter
{
    public function __construct(
        private MetricSink $metricSink,
        private MetricDefinitions $metricDefinitions,
    ) {}

    public function render(): string
    {
        $lines = [];
        $groupedCounters = [];

        foreach ($this->metricSink->counters() as $key => $value) {
            [$name, $labels] = $this->parseCounterKey($key);

            $groupedCounters[$name][] = [
                'labels' => $labels,
                'value' => $value,
            ];
        }

        ksort($groupedCounters);

        foreach ($groupedCounters as $name => $samples) {
            $definition = $this->metricDefinitions->all()[$name] ?? null;

            if ($definition === null || $definition->type !== 'counter') {
                continue;
            }

            $lines[] = '# HELP '.$definition->name.' '.$definition->help;
            $lines[] = '# TYPE '.$definition->name.' '.$definition->type;

            foreach ($samples as $sample) {
                $lines[] = $definition->name.$this->formatLabels($sample['labels']).' '.$sample['value'];
            }

            $lines[] = '';
        }

        $groupedHistograms = [];

        foreach ($this->metricSink->histograms() as $key => $values) {
            [$name, $labels] = $this->parseCounterKey($key);

            $groupedHistograms[$name][] = [
                'labels' => $labels,
                'values' => $values,
            ];
        }

        ksort($groupedHistograms);

        foreach ($groupedHistograms as $name => $samples) {
            $definition = $this->metricDefinitions->all()[$name] ?? null;

            if ($definition === null || $definition->type !== 'histogram') {
                continue;
            }

            $lines[] = '# HELP '.$definition->name.' '.$definition->help;
            $lines[] = '# TYPE '.$definition->name.' '.$definition->type;

            foreach ($samples as $sample) {
                $bucketValues = $sample['values']['buckets'];
                uksort($bucketValues, static fn (string $a, string $b): int => (float) $a <=> (float) $b);

                foreach ($bucketValues as $bucket => $count) {
                    $lines[] = $definition->name.'_bucket'.$this->formatLabels([
                        ...$sample['labels'],
                        'le' => $bucket,
                    ]).' '.$count;
                }

                $lines[] = $definition->name.'_bucket'.$this->formatLabels([
                    ...$sample['labels'],
                    'le' => '+Inf',
                ]).' '.$sample['values']['count'];
                $lines[] = $definition->name.'_sum'.$this->formatLabels($sample['labels']).' '.$sample['values']['sum'];
                $lines[] = $definition->name.'_count'.$this->formatLabels($sample['labels']).' '.$sample['values']['count'];
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
