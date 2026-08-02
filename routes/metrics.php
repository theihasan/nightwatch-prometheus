<?php

use Ihasan\NightwatchPromethus\Contracts\MetricsExporter;
use Illuminate\Support\Facades\Route;

$middleware = config('nightwatch-promethus.routes.metrics.middleware', []);
$path = ltrim((string) config('nightwatch-promethus.routes.metrics.path', 'metrics'), '/');

Route::middleware($middleware)->get($path, function (MetricsExporter $exporter) {
    return response($exporter->render(), 200, [
        'Content-Type' => 'text/plain; version=0.0.4; charset=utf-8',
    ]);
})->name('nightwatch-promethus.metrics');
