<?php

namespace Ihasan\NightwatchPromethus\Metrics;

class MetricDefinition
{
    /**
     * @param list<string> $labels
     */
    public function __construct(
        public string $name,
        public string $type,
        public string $help,
        public array $labels,
    ) {
        //
    }
}
