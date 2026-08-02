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
            'command' => $this->recordCommandMetric($record),
            'exception' => $this->recordExceptionMetric($record),
            'job-attempt' => $this->recordJobAttemptMetric($record),
            'queued-job' => $this->recordQueuedJobMetric($record),
            'query' => $this->recordQueryMetric($record),
            'outgoing-request' => $this->recordOutgoingRequestMetric($record),
            'scheduled-task' => $this->recordScheduledTaskMetric($record),
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
     */
    private function recordExceptionMetric(array $record): void
    {
        $executionSource = is_string($record['execution_source'] ?? null) && $record['execution_source'] !== ''
            ? $record['execution_source']
            : 'unknown';
        $class = is_string($record['class'] ?? null) && $record['class'] !== ''
            ? $record['class']
            : 'unknown';
        $result = (bool) ($record['handled'] ?? false) ? 'handled' : 'unhandled';

        $this->metricSink->incrementCounter($this->metricDefinitions->exceptionsTotal()->name, [
            'execution_source' => $executionSource,
            'class' => $class,
            'result' => $result,
        ]);
    }

    /**
     * @param array<mixed> $record
     */
    private function recordQueryMetric(array $record): void
    {
        $executionSource = is_string($record['execution_source'] ?? null) && $record['execution_source'] !== ''
            ? $record['execution_source']
            : 'unknown';
        $connection = is_string($record['connection'] ?? null) && $record['connection'] !== ''
            ? $record['connection']
            : 'unknown';
        $connectionType = is_string($record['connection_type'] ?? null) && $record['connection_type'] !== ''
            ? $record['connection_type']
            : 'unknown';

        $labels = [
            'execution_source' => $executionSource,
            'connection' => $connection,
            'connection_type' => $connectionType,
        ];

        $this->metricSink->incrementCounter($this->metricDefinitions->dbQueriesTotal()->name, $labels);

        if (is_numeric($record['duration'] ?? null)) {
            $definition = $this->metricDefinitions->dbQueryDurationSeconds();

            $this->metricSink->observeHistogram(
                $definition->name,
                $labels,
                (float) $record['duration'] / 1_000_000,
                $definition->buckets,
            );
        }
    }

    /**
     * @param array<mixed> $record
     */
    private function recordOutgoingRequestMetric(array $record): void
    {
        $executionSource = is_string($record['execution_source'] ?? null) && $record['execution_source'] !== ''
            ? $record['execution_source']
            : 'unknown';
        $host = is_string($record['host'] ?? null) && $record['host'] !== ''
            ? $record['host']
            : 'unknown';
        $method = is_string($record['method'] ?? null) && $record['method'] !== ''
            ? $record['method']
            : 'UNKNOWN';
        $statusCode = (string) ($record['status_code'] ?? 'unknown');

        $labels = [
            'execution_source' => $executionSource,
            'host' => $host,
            'method' => $method,
            'status_code' => $statusCode,
        ];

        $this->metricSink->incrementCounter($this->metricDefinitions->outgoingHttpRequestsTotal()->name, $labels);

        if (is_numeric($record['duration'] ?? null)) {
            $definition = $this->metricDefinitions->outgoingHttpRequestDurationSeconds();

            $this->metricSink->observeHistogram(
                $definition->name,
                $labels,
                (float) $record['duration'] / 1_000_000,
                $definition->buckets,
            );
        }
    }

    /**
     * @param array<mixed> $record
     */
    private function recordCommandMetric(array $record): void
    {
        $name = is_string($record['name'] ?? null) && $record['name'] !== ''
            ? $record['name']
            : 'unknown';
        $exitCode = (string) ($record['exit_code'] ?? 'unknown');

        $labels = [
            'name' => $name,
            'exit_code' => $exitCode,
        ];

        $this->metricSink->incrementCounter($this->metricDefinitions->commandExecutionsTotal()->name, $labels);

        if (is_numeric($record['duration'] ?? null)) {
            $definition = $this->metricDefinitions->commandDurationSeconds();

            $this->metricSink->observeHistogram(
                $definition->name,
                $labels,
                (float) $record['duration'] / 1_000_000,
                $definition->buckets,
            );
        }
    }

    /**
     * @param array<mixed> $record
     */
    private function recordQueuedJobMetric(array $record): void
    {
        $executionSource = is_string($record['execution_source'] ?? null) && $record['execution_source'] !== ''
            ? $record['execution_source']
            : 'unknown';
        $name = is_string($record['name'] ?? null) && $record['name'] !== ''
            ? $record['name']
            : 'unknown';
        $connection = is_string($record['connection'] ?? null) && $record['connection'] !== ''
            ? $record['connection']
            : 'unknown';
        $queue = is_string($record['queue'] ?? null) && $record['queue'] !== ''
            ? $record['queue']
            : 'unknown';

        $this->metricSink->incrementCounter($this->metricDefinitions->jobsQueuedTotal()->name, [
            'execution_source' => $executionSource,
            'name' => $name,
            'connection' => $connection,
            'queue' => $queue,
        ]);
    }

    /**
     * @param array<mixed> $record
     */
    private function recordJobAttemptMetric(array $record): void
    {
        $name = is_string($record['name'] ?? null) && $record['name'] !== ''
            ? $record['name']
            : 'unknown';
        $connection = is_string($record['connection'] ?? null) && $record['connection'] !== ''
            ? $record['connection']
            : 'unknown';
        $queue = is_string($record['queue'] ?? null) && $record['queue'] !== ''
            ? $record['queue']
            : 'unknown';
        $result = is_string($record['status'] ?? null) && $record['status'] !== ''
            ? $record['status']
            : 'unknown';

        $labels = [
            'name' => $name,
            'connection' => $connection,
            'queue' => $queue,
            'result' => $result,
        ];

        $this->metricSink->incrementCounter($this->metricDefinitions->jobAttemptsTotal()->name, $labels);

        if (is_numeric($record['duration'] ?? null)) {
            $definition = $this->metricDefinitions->jobAttemptDurationSeconds();

            $this->metricSink->observeHistogram(
                $definition->name,
                $labels,
                (float) $record['duration'] / 1_000_000,
                $definition->buckets,
            );
        }
    }

    /**
     * @param array<mixed> $record
     */
    private function recordScheduledTaskMetric(array $record): void
    {
        $name = is_string($record['name'] ?? null) && $record['name'] !== ''
            ? $record['name']
            : 'unknown';
        $result = is_string($record['status'] ?? null) && $record['status'] !== ''
            ? $record['status']
            : 'unknown';

        $this->metricSink->incrementCounter($this->metricDefinitions->scheduledTaskRunsTotal()->name, [
            'name' => $name,
            'result' => $result,
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
