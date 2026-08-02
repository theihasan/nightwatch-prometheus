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

    /**
     * @return array<string, MetricDefinition>
     */
    public function all(): array
    {
        $definitions = [
            $this->httpRequestsTotal(),
            $this->logsTotal(),
        ];

        $indexedDefinitions = [];

        foreach ($definitions as $definition) {
            $indexedDefinitions[$definition->name] = $definition;
        }

        return $indexedDefinitions;
    }
}
