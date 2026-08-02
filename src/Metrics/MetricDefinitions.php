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

    /**
     * @return array<string, MetricDefinition>
     */
    public function all(): array
    {
        $definitions = [
            $this->httpRequestsTotal(),
        ];

        $indexedDefinitions = [];

        foreach ($definitions as $definition) {
            $indexedDefinitions[$definition->name] = $definition;
        }

        return $indexedDefinitions;
    }
}
