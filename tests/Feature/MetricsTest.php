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

    public function test_request_record_updates_peak_memory_histogram_and_exports_it(): void
    {
        $this->app->make(NightwatchPromethusIngest::class)->write([
            't' => 'request',
            'method' => 'GET',
            'route_path' => '/health',
            'status_code' => 200,
            'duration' => 10000,
            'peak_memory_usage' => 4194304,
        ]);

        $snapshot = $this->app->make(NightwatchPromethusState::class)->snapshot();
        $output = $this->app->make(MetricsExporter::class)->render();

        $histogram = $snapshot['histograms']['nightwatch_http_request_peak_memory_bytes|method=GET,route=/health,status_code=200'];

        $this->assertSame(4194304.0, $histogram['sum']);
        $this->assertSame(1.0, $histogram['count']);
        $this->assertSame(1.0, $histogram['buckets']['4194304']);
        $this->assertStringContainsString('# HELP nightwatch_http_request_peak_memory_bytes Peak memory usage on HTTP requests observed by Nightwatch Promethus', $output);
        $this->assertStringContainsString('# TYPE nightwatch_http_request_peak_memory_bytes histogram', $output);
        $this->assertStringContainsString('nightwatch_http_request_peak_memory_bytes_bucket{method="GET",route="/health",status_code="200",le="4194304"} 1', $output);
        $this->assertStringContainsString('nightwatch_http_request_peak_memory_bytes_bucket{method="GET",route="/health",status_code="200",le="+Inf"} 1', $output);
        $this->assertStringContainsString('nightwatch_http_request_peak_memory_bytes_sum{method="GET",route="/health",status_code="200"} 4194304', $output);
        $this->assertStringContainsString('nightwatch_http_request_peak_memory_bytes_count{method="GET",route="/health",status_code="200"} 1', $output);
    }

    public function test_request_record_updates_stage_duration_histograms_and_exports_them(): void
    {
        $this->app->make(NightwatchPromethusIngest::class)->write([
            't' => 'request',
            'method' => 'GET',
            'route_path' => '/health',
            'status_code' => 200,
            'duration' => 10000,
            'bootstrap' => 1000,
            'before_middleware' => 2000,
            'action' => 3000,
            'render' => 4000,
            'after_middleware' => 5000,
            'sending' => 6000,
            'terminating' => 7000,
        ]);

        $snapshot = $this->app->make(NightwatchPromethusState::class)->snapshot();
        $output = $this->app->make(MetricsExporter::class)->render();

        $histogram = $snapshot['histograms']['nightwatch_http_request_stage_duration_seconds|method=GET,route=/health,stage=bootstrap,status_code=200'];

        $this->assertSame(0.001, $histogram['sum']);
        $this->assertSame(1.0, $histogram['count']);
        $this->assertSame(1.0, $histogram['buckets']['0.001']);
        $this->assertStringContainsString('# HELP nightwatch_http_request_stage_duration_seconds HTTP request stage duration observed by Nightwatch Promethus', $output);
        $this->assertStringContainsString('# TYPE nightwatch_http_request_stage_duration_seconds histogram', $output);
        $this->assertStringContainsString('nightwatch_http_request_stage_duration_seconds_bucket{method="GET",route="/health",stage="bootstrap",status_code="200",le="0.001"} 1', $output);
        $this->assertStringContainsString('nightwatch_http_request_stage_duration_seconds_sum{method="GET",route="/health",stage="bootstrap",status_code="200"} 0.001', $output);
        $this->assertStringContainsString('nightwatch_http_request_stage_duration_seconds_count{method="GET",route="/health",stage="bootstrap",status_code="200"} 1', $output);
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

    public function test_exception_record_increments_exceptions_counter_and_exports_it(): void
    {
        $this->app->make(NightwatchPromethusIngest::class)->write([
            't' => 'exception',
            'execution_source' => 'request',
            'class' => 'RuntimeException',
            'handled' => false,
        ]);

        $snapshot = $this->app->make(NightwatchPromethusState::class)->snapshot();
        $output = $this->app->make(MetricsExporter::class)->render();

        $this->assertSame(1.0, $snapshot['metrics']['nightwatch_exceptions_total|class=RuntimeException,execution_source=request,result=unhandled']);
        $this->assertStringContainsString('# HELP nightwatch_exceptions_total Total exceptions observed by Nightwatch Promethus', $output);
        $this->assertStringContainsString('# TYPE nightwatch_exceptions_total counter', $output);
        $this->assertStringContainsString('nightwatch_exceptions_total{class="RuntimeException",execution_source="request",result="unhandled"} 1', $output);
    }
}
