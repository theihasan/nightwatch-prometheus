<?php

namespace Ihasan\NightwatchPromethus\Metrics;

class MetricDefinitions
{
    public function httpRequestsTotal(): MetricDefinition
    {
        return new MetricDefinition(
            'nightwatch_http_requests_total',
            'counter',
            'Total HTTP requests observed by Nightwatch Promethus',
            ['method', 'route', 'status_code'],
        );
    }

    public function logsTotal(): MetricDefinition
    {
        return new MetricDefinition(
            'nightwatch_logs_total',
            'counter',
            'Total log records observed by Nightwatch Promethus',
            ['level'],
        );
    }

    public function httpRequestDurationSeconds(): MetricDefinition
    {
        return new MetricDefinition(
            'nightwatch_http_request_duration_seconds',
            'histogram',
            'HTTP request duration observed by Nightwatch Promethus',
            ['method', 'route', 'status_code'],
            [0.001, 0.005, 0.01, 0.025, 0.05, 0.1, 0.25, 0.5, 1.0, 2.5, 5.0, 10.0],
        );
    }

    public function httpRequestQueriesTotal(): MetricDefinition
    {
        return new MetricDefinition(
            'nightwatch_http_request_queries_total',
            'counter',
            'Total queries counted on HTTP requests by Nightwatch Promethus',
            ['method', 'route', 'status_code'],
        );
    }

    public function httpRequestPeakMemoryBytes(): MetricDefinition
    {
        return new MetricDefinition(
            'nightwatch_http_request_peak_memory_bytes',
            'histogram',
            'Peak memory usage on HTTP requests observed by Nightwatch Promethus',
            ['method', 'route', 'status_code'],
            [1048576.0, 4194304.0, 16777216.0, 67108864.0, 268435456.0, 1073741824.0],
        );
    }

    public function httpRequestStageDurationSeconds(): MetricDefinition
    {
        return new MetricDefinition(
            'nightwatch_http_request_stage_duration_seconds',
            'histogram',
            'HTTP request stage duration observed by Nightwatch Promethus',
            ['method', 'route', 'status_code', 'stage'],
            [0.001, 0.005, 0.01, 0.025, 0.05, 0.1, 0.25, 0.5, 1.0, 2.5, 5.0, 10.0],
        );
    }

    public function exceptionsTotal(): MetricDefinition
    {
        return new MetricDefinition(
            'nightwatch_exceptions_total',
            'counter',
            'Total exceptions observed by Nightwatch Promethus',
            ['execution_source', 'class', 'result'],
        );
    }

    public function dbQueriesTotal(): MetricDefinition
    {
        return new MetricDefinition(
            'nightwatch_db_queries_total',
            'counter',
            'Total database queries observed by Nightwatch Promethus',
            ['execution_source', 'connection', 'connection_type'],
        );
    }

    public function dbQueryDurationSeconds(): MetricDefinition
    {
        return new MetricDefinition(
            'nightwatch_db_query_duration_seconds',
            'histogram',
            'Database query duration observed by Nightwatch Promethus',
            ['execution_source', 'connection', 'connection_type'],
            [0.001, 0.005, 0.01, 0.025, 0.05, 0.1, 0.25, 0.5, 1.0, 2.5, 5.0, 10.0],
        );
    }

    public function outgoingHttpRequestsTotal(): MetricDefinition
    {
        return new MetricDefinition(
            'nightwatch_outgoing_http_requests_total',
            'counter',
            'Total outgoing HTTP requests observed by Nightwatch Promethus',
            ['execution_source', 'host', 'method', 'status_code'],
        );
    }

    public function outgoingHttpRequestDurationSeconds(): MetricDefinition
    {
        return new MetricDefinition(
            'nightwatch_outgoing_http_request_duration_seconds',
            'histogram',
            'Outgoing HTTP request duration observed by Nightwatch Promethus',
            ['execution_source', 'host', 'method', 'status_code'],
            [0.001, 0.005, 0.01, 0.025, 0.05, 0.1, 0.25, 0.5, 1.0, 2.5, 5.0, 10.0],
        );
    }

    public function commandExecutionsTotal(): MetricDefinition
    {
        return new MetricDefinition(
            'nightwatch_command_executions_total',
            'counter',
            'Total command executions observed by Nightwatch Promethus',
            ['name', 'exit_code'],
        );
    }

    /**
     * @return array<string, MetricDefinition>
     */
    public function all(): array
    {
        $definitions = [
            $this->httpRequestsTotal(),
            $this->logsTotal(),
            $this->httpRequestDurationSeconds(),
            $this->httpRequestQueriesTotal(),
            $this->httpRequestPeakMemoryBytes(),
            $this->httpRequestStageDurationSeconds(),
            $this->exceptionsTotal(),
            $this->dbQueriesTotal(),
            $this->dbQueryDurationSeconds(),
            $this->outgoingHttpRequestsTotal(),
            $this->outgoingHttpRequestDurationSeconds(),
            $this->commandExecutionsTotal(),
        ];

        $indexedDefinitions = [];

        foreach ($definitions as $definition) {
            $indexedDefinitions[$definition->name] = $definition;
        }

        return $indexedDefinitions;
    }
}
