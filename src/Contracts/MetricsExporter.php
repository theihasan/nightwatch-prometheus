<?php

namespace Ihasan\NightwatchPromethus\Contracts;

interface MetricsExporter
{
    public function render(): string;
}
