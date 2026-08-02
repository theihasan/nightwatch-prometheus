<?php

namespace Ihasan\NightwatchPromethus\Ingest;

use Ihasan\NightwatchPromethus\Contracts\MetricSink;
use Ihasan\NightwatchPromethus\Metrics\MetricDefinitions;
use Laravel\Nightwatch\Contracts\Ingest as IngestContract;

class NightwatchPromethusIngest implements IngestContract
{
    private int $writeCount = 0;

    private int $writeNowCount = 0;

    private ?string $lastRecordType = null;

    /**
     * @var array<string, int>
     */
    private array $recordTypeCounts = [];

    public function __construct(
        private MetricSink $metricSink,
        private MetricDefinitions $metricDefinitions,
    ) {}

    public function write(array $record): void
    {
        $this->writeCount++;

        $this->trackRecord($record);
        $this->recordMetric($record);
    }

    public function writeNow(array $record): void
    {
        $this->writeNowCount++;

        $this->trackRecord($record);
        $this->recordMetric($record);
    }

    public function ping(): void
    {
       
    }

    public function shouldDigest(bool $bool = true): void
    {
        
    }

    public function shouldDigestWhenBufferIsFull(bool $bool = true): void
    {
        
    }

    public function digest(): void
    {
        
    }

    public function flush(): void
    {

    }

    public function writeCount(): int
    {
        return $this->writeCount;
    }

    public function writeNowCount(): int
    {
        return $this->writeNowCount;
    }

    public function lastRecordType(): ?string
    {
        return $this->lastRecordType;
    }

    /**
     * @return array<string, int>
     */
    public function recordTypeCounts(): array
    {
        return $this->recordTypeCounts;
    }

    /**
     * @param array<mixed> $record
     */
    private function trackRecord(array $record): void
    {
        $type = is_string($record['t'] ?? null) ? $record['t'] : 'unknown';

        $this->lastRecordType = $type;
        $this->recordTypeCounts[$type] = ($this->recordTypeCounts[$type] ?? 0) + 1;
    }

    /**
     * @param array<mixed> $record
     */
    private function recordMetric(array $record): void
    {
        match ($record['t'] ?? null) {
            'request' => $this->recordRequestMetric($record),
            'log' => $this->recordLogMetric($record),
            default => null,
        };
    }

    /**
     * @param array<mixed> $record
     */
    private function recordRequestMetric(array $record): void
    {
        $method = is_string($record['method'] ?? null) ? $record['method'] : 'UNKNOWN';
        $route = is_string($record['route_path'] ?? null) && $record['route_path'] !== ''
            ? $record['route_path']
            : '__unmatched__';
        $statusCode = (string) ($record['status_code'] ?? 'unknown');
        $labels = [
            'method' => $method,
            'route' => $route,
            'status_code' => $statusCode,
        ];

        $this->metricSink->incrementCounter($this->metricDefinitions->httpRequestsTotal()->name, $labels);

        $duration = (float) (($record['duration'] ?? 0) / 1_000_000);
        $definition = $this->metricDefinitions->httpRequestDurationSeconds();

        $this->metricSink->observeHistogram($definition->name, $labels, $duration, $definition->buckets);

        $stageDefinition = $this->metricDefinitions->httpRequestStageDurationSeconds();

        foreach ($this->requestStageDurations($record) as $stage => $stageDuration) {
            $this->metricSink->observeHistogram(
                $stageDefinition->name,
                [...$labels, 'stage' => $stage],
                $stageDuration,
                $stageDefinition->buckets,
            );
        }

        $this->metricSink->incrementCounter(
            $this->metricDefinitions->httpRequestQueriesTotal()->name,
            $labels,
            (int) ($record['queries'] ?? 0),
        );

        if (is_numeric($record['peak_memory_usage'] ?? null)) {
            $peakMemoryDefinition = $this->metricDefinitions->httpRequestPeakMemoryBytes();

            $this->metricSink->observeHistogram(
                $peakMemoryDefinition->name,
                $labels,
                (float) $record['peak_memory_usage'],
                $peakMemoryDefinition->buckets,
            );
        }
    }

    /**
     * @param array<mixed> $record
     */
    private function recordLogMetric(array $record): void
    {
        $level = is_string($record['level'] ?? null) && $record['level'] !== ''
            ? $record['level']
            : 'unknown';

        $this->metricSink->incrementCounter($this->metricDefinitions->logsTotal()->name, [
            'level' => $level,
        ]);
    }

    /**
     * @param array<mixed> $record
     * @return array<string, float>
     */
    private function requestStageDurations(array $record): array
    {
        $stageFields = [
            'bootstrap',
            'before_middleware',
            'action',
            'render',
            'after_middleware',
            'sending',
            'terminating',
        ];

        $durations = [];

        foreach ($stageFields as $stageField) {
            if (! is_numeric($record[$stageField] ?? null)) {
                continue;
            }

            $durations[$stageField] = (float) $record[$stageField] / 1_000_000;
        }

        return $durations;
    }
}
