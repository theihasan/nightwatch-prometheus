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
            'duration' => 10000,
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
            'duration' => 10000,
        ]);

        $output = $this->app->make(MetricsExporter::class)->render();

        $this->assertStringContainsString('# HELP nightwatch_http_requests_total Total HTTP requests observed by Nightwatch Promethus', $output);
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
            'duration' => 10000,
        ]);

        $response = $this->get('/metrics');

        $response->assertOk();
        $response->assertHeader('Content-Type', 'text/plain; version=0.0.4; charset=utf-8');
        $response->assertSee('# TYPE nightwatch_http_requests_total counter', false);
    }

    public function test_request_record_updates_request_duration_histogram_and_exports_it(): void
    {
        $this->app->make(NightwatchPromethusIngest::class)->write([
            't' => 'request',
            'method' => 'GET',
            'route_path' => '/health',
            'status_code' => 200,
            'duration' => 10000,
        ]);

        $snapshot = $this->app->make(NightwatchPromethusState::class)->snapshot();
        $output = $this->app->make(MetricsExporter::class)->render();

        $histogram = $snapshot['histograms']['nightwatch_http_request_duration_seconds|method=GET,route=/health,status_code=200'];

        $this->assertSame(0.01, $histogram['sum']);
        $this->assertSame(1.0, $histogram['count']);
        $this->assertSame(1.0, $histogram['buckets']['0.01']);
        $this->assertStringContainsString('# TYPE nightwatch_http_request_duration_seconds histogram', $output);
        $this->assertStringContainsString('nightwatch_http_request_duration_seconds_bucket{method="GET",route="/health",status_code="200",le="0.01"} 1', $output);
        $this->assertStringContainsString('nightwatch_http_request_duration_seconds_bucket{method="GET",route="/health",status_code="200",le="+Inf"} 1', $output);
        $this->assertStringContainsString('nightwatch_http_request_duration_seconds_sum{method="GET",route="/health",status_code="200"} 0.01', $output);
        $this->assertStringContainsString('nightwatch_http_request_duration_seconds_count{method="GET",route="/health",status_code="200"} 1', $output);
    }

    public function test_request_record_increments_request_queries_counter_and_exports_it(): void
    {
        $this->app->make(NightwatchPromethusIngest::class)->write([
            't' => 'request',
            'method' => 'GET',
            'route_path' => '/health',
            'status_code' => 200,
            'duration' => 10000,
            'queries' => 3,
        ]);

        $snapshot = $this->app->make(NightwatchPromethusState::class)->snapshot();
        $output = $this->app->make(MetricsExporter::class)->render();

        $this->assertSame(3.0, $snapshot['metrics']['nightwatch_http_request_queries_total|method=GET,route=/health,status_code=200']);
        $this->assertStringContainsString('# HELP nightwatch_http_request_queries_total Total queries counted on HTTP requests by Nightwatch Promethus', $output);
        $this->assertStringContainsString('# TYPE nightwatch_http_request_queries_total counter', $output);
        $this->assertStringContainsString('nightwatch_http_request_queries_total{method="GET",route="/health",status_code="200"} 3', $output);
    }

    public function test_log_record_increments_logs_counter_and_exports_it(): void
    {
        $this->app->make(NightwatchPromethusIngest::class)->write([
            't' => 'log',
            'level' => 'error',
        ]);

        $snapshot = $this->app->make(NightwatchPromethusState::class)->snapshot();
        $output = $this->app->make(MetricsExporter::class)->render();

        $this->assertSame(1.0, $snapshot['metrics']['nightwatch_logs_total|level=error']);
        $this->assertStringContainsString('# HELP nightwatch_logs_total Total log records observed by Nightwatch Promethus', $output);
        $this->assertStringContainsString('# TYPE nightwatch_logs_total counter', $output);
        $this->assertStringContainsString('nightwatch_logs_total{level="error"} 1', $output);
    }
}
