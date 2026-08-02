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

    /**
     * @return array<string, MetricDefinition>
     */
    public function all(): array
    {
        $definitions = [
            $this->httpRequestsTotal(),
            $this->logsTotal(),
            $this->httpRequestDurationSeconds(),
        ];

        $indexedDefinitions = [];

        foreach ($definitions as $definition) {
            $indexedDefinitions[$definition->name] = $definition;
        }

        return $indexedDefinitions;
    }
}
