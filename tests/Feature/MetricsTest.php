<?php

namespace Ihasan\NightwatchPromethus\Tests\Feature;

use Ihasan\NightwatchPromethus\Contracts\MetricsExporter;
use Ihasan\NightwatchPromethus\Debug\NightwatchPromethusState;
use Ihasan\NightwatchPromethus\Ingest\NightwatchPromethusIngest;
use Ihasan\NightwatchPromethus\Tests\TestCase;

class MetricsTest extends TestCase
{
    public function test_request_record_increments_http_request_counter(): void
    {
        $this->app->make(NightwatchPromethusIngest::class)->write([
            't' => 'request',
            'method' => 'GET',
            'route_path' => '/health',
            'status_code' => 200,
        ]);

        $snapshot = $this->app->make(NightwatchPromethusState::class)->snapshot();

        $this->assertSame(1.0, $snapshot['metrics']['nightwatch_http_requests_total|method=GET,route=/health,status_code=200']);
    }

    public function test_exporter_renders_prometheus_counter_output(): void
    {
        $this->app->make(NightwatchPromethusIngest::class)->write([
            't' => 'request',
            'method' => 'GET',
            'route_path' => '/health',
            'status_code' => 200,
        ]);

        $output = $this->app->make(MetricsExporter::class)->render();

        $this->assertStringContainsString('# TYPE nightwatch_http_requests_total counter', $output);
        $this->assertStringContainsString('nightwatch_http_requests_total{method="GET",route="/health",status_code="200"} 1', $output);
    }

    public function test_metrics_route_returns_plain_text_prometheus_response(): void
    {
        $this->app->make(NightwatchPromethusIngest::class)->write([
            't' => 'request',
            'method' => 'GET',
            'route_path' => '/health',
            'status_code' => 200,
        ]);

        $response = $this->get('/metrics');

        $response->assertOk();
        $response->assertHeader('Content-Type', 'text/plain; version=0.0.4; charset=utf-8');
        $response->assertSee('# TYPE nightwatch_http_requests_total counter', false);
    }
}
