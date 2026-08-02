<?php

namespace Ihasan\NightwatchPromethus\Debug;

use Ihasan\NightwatchPromethus\Contracts\MetricSink;
use Ihasan\NightwatchPromethus\Ingest\NightwatchPromethusIngest;

class NightwatchPromethusState
{
    public function __construct(
        private NightwatchPromethusIngest $ingest,
        private MetricSink $metricSink,
    ) {
        //
    }

    /**
     * @return array{
     *     ingest: array{write_count: int, write_now_count: int, last_record_type: ?string, record_type_counts: array<string, int>},
     *     metrics: array<string, float>,
     *     histograms: array<string, array{buckets: array<string, float>, sum: float, count: float}>
     * }
     */
    public function snapshot(): array
    {
        return [
            'ingest' => [
                'write_count' => $this->ingest->writeCount(),
                'write_now_count' => $this->ingest->writeNowCount(),
                'last_record_type' => $this->ingest->lastRecordType(),
                'record_type_counts' => $this->ingest->recordTypeCounts(),
            ],
            'metrics' => $this->metricSink->counters(),
            'histograms' => $this->metricSink->histograms(),
        ];
    }
}
