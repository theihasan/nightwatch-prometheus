<?php

namespace Ihasan\NightwatchPromethus\Ingest;

use Laravel\Nightwatch\Contracts\Ingest as IngestContract;

class NightwatchPromethusIngest implements IngestContract
{
    public function write(array $record): void
    {
       
    }

    public function writeNow(array $record): void
    {
       
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
}
