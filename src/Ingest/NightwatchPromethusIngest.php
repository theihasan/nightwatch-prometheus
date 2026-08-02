<?php

namespace Ihasan\NightwatchPromethus\Ingest;

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

    public function write(array $record): void
    {
        $this->writeCount++;

        $this->trackRecord($record);
    }

    public function writeNow(array $record): void
    {
        $this->writeNowCount++;

        $this->trackRecord($record);
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
}
