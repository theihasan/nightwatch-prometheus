<?php

namespace Ihasan\NightwatchPromethus\Metrics;

class MetricDefinition
{
    /**
     * @param list<string> $labels
     * @param list<float> $buckets
     */
    public function __construct(
        public string $name,
        public string $type,
        public string $help,
        public array $labels,
        public array $buckets = [],
    ) {
        //
    }
}
