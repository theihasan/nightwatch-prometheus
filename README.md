# Nightwatch Promethus

`theihasan/nightwatch-promethus` exposes Laravel Nightwatch records as Prometheus metrics.

It runs on top of `laravel/nightwatch`, but it does not require a Nightwatch subscription. Nightwatch is used as the in-app event collection layer, while this package replaces Nightwatch's default ingest path and exports metrics for Prometheus scraping.

## Requirements

- PHP 8.2+
- Laravel 10, 11, 12, or 13
- `laravel/nightwatch` ^1.28
- Redis for shared metric storage in real applications

## How It Works

- Laravel Nightwatch collects request, query, queue, exception, log, and related runtime records.
- This package replaces Nightwatch's ingest implementation.
- Records are converted into Prometheus-friendly counters and histograms.
- Metrics are stored in Redis by default.
- Prometheus scrapes the `/metrics` endpoint.

## Nightwatch Subscription

This package does not need a Nightwatch subscription.

You still install and enable `laravel/nightwatch`, but you do not need to send data to the Nightwatch hosted service. This package uses Nightwatch's local record generation only.

## Installation

Install Nightwatch and this package:

```bash
composer require laravel/nightwatch theihasan/nightwatch-promethus
```

Publish the package config:

```bash
php artisan vendor:publish --tag=nightwatch-promethus-config
```

## Minimal Nightwatch Setup

Enable Nightwatch in your app and keep sampling at `1.0` for accurate counters.

Example `.env` values:

```env
NIGHTWATCH_ENABLED=true
NIGHTWATCH_TOKEN=dummy-token

NIGHTWATCH_REQUEST_SAMPLE_RATE=1.0
NIGHTWATCH_COMMAND_SAMPLE_RATE=1.0
NIGHTWATCH_EXCEPTION_SAMPLE_RATE=1.0
NIGHTWATCH_SCHEDULED_TASK_SAMPLE_RATE=1.0

NIGHTWATCH_PROMETHUS_ENABLED=true
NIGHTWATCH_PROMETHUS_STORAGE_DRIVER=redis
NIGHTWATCH_PROMETHUS_REDIS_CONNECTION=default
NIGHTWATCH_PROMETHUS_REDIS_PREFIX=nightwatch_promethus
```

Notes:

- The token does not need to be a real Nightwatch subscription token for this package's exporter flow.
- Redis is recommended because metrics must be shared across requests, workers, and scheduled/queue processes.

## Routes

By default:

- metrics endpoint: `/metrics`
- debug endpoint: `/nightwatch-promethus/debug`

The metrics route is enabled by default.

The debug route is disabled by default in production unless explicitly enabled.

Configurable route settings live in `config/nightwatch-promethus.php`.

## Available Metrics

Currently implemented:

- `nightwatch_http_requests_total`
- `nightwatch_http_request_duration_seconds`
- `nightwatch_http_request_stage_duration_seconds`
- `nightwatch_http_request_peak_memory_bytes`
- `nightwatch_http_request_queries_total`
- `nightwatch_exceptions_total`
- `nightwatch_db_queries_total`
- `nightwatch_db_query_duration_seconds`
- `nightwatch_outgoing_http_requests_total`
- `nightwatch_outgoing_http_request_duration_seconds`
- `nightwatch_jobs_queued_total`
- `nightwatch_job_attempts_total`
- `nightwatch_job_attempt_duration_seconds`
- `nightwatch_scheduled_task_runs_total`
- `nightwatch_command_executions_total`
- `nightwatch_command_duration_seconds`
- `nightwatch_cache_events_total`
- `nightwatch_logs_total`
- `nightwatch_notifications_total`
- `nightwatch_notification_duration_seconds`

## Log Metric Caution

`nightwatch_logs_total` is verified working through the dedicated Nightwatch log channel.

If you want normal application logs to be captured by Nightwatch in a typical Laravel stack setup, include the `nightwatch` channel in your log stack.

Example:

```env
LOG_CHANNEL=stack
LOG_STACK=single,nightwatch
```

Then clear config:

```bash
php artisan config:clear
```

## Known Limitation

These two metrics are implemented in the package, but they may not appear in real worker flows until the upstream Nightwatch issue is fixed:

- `nightwatch_job_attempts_total`
- `nightwatch_job_attempt_duration_seconds`

Upstream issue:

- `laravel/nightwatch#406`
- https://github.com/laravel/nightwatch/issues/406

Current status:

- `nightwatch_jobs_queued_total` works
- `job-attempt` record emission appears to be blocked by Nightwatch worker behavior in real `queue:work` processing

## Example PromQL

Request rate:

```promql
sum(rate(nightwatch_http_requests_total[5m]))
```

Requests by route:

```promql
sum by (route) (rate(nightwatch_http_requests_total[5m]))
```

95th percentile request duration:

```promql
histogram_quantile(
  0.95,
  sum by (le, route) (rate(nightwatch_http_request_duration_seconds_bucket[5m]))
)
```

Query count by route:

```promql
sum by (route) (rate(nightwatch_http_request_queries_total[5m]))
```

Database query rate by connection:

```promql
sum by (connection, connection_type) (rate(nightwatch_db_queries_total[5m]))
```

95th percentile database query duration:

```promql
histogram_quantile(
  0.95,
  sum by (le, connection) (rate(nightwatch_db_query_duration_seconds_bucket[5m]))
)
```

Outgoing HTTP request rate by host:

```promql
sum by (host) (rate(nightwatch_outgoing_http_requests_total[5m]))
```

95th percentile outgoing HTTP request duration:

```promql
histogram_quantile(
  0.95,
  sum by (le, host) (rate(nightwatch_outgoing_http_request_duration_seconds_bucket[5m]))
)
```

Unhandled exceptions:

```promql
sum(rate(nightwatch_exceptions_total{result="unhandled"}[5m]))
```

Command execution rate:

```promql
sum by (name) (rate(nightwatch_command_executions_total[5m]))
```

Job attempts by result:

```promql
sum by (result) (rate(nightwatch_job_attempts_total[5m]))
```

Scheduled task runs:

```promql
sum by (name, result) (rate(nightwatch_scheduled_task_runs_total[15m]))
```

Cache hit rate:

```promql
sum(rate(nightwatch_cache_events_total{cache_event_type="hit"}[5m]))
```

Cache miss rate:

```promql
sum(rate(nightwatch_cache_events_total{cache_event_type="miss"}[5m]))
```

Cache hit ratio:

```promql
sum(rate(nightwatch_cache_events_total{cache_event_type="hit"}[5m]))
/
(
  sum(rate(nightwatch_cache_events_total{cache_event_type="hit"}[5m]))
  +
  sum(rate(nightwatch_cache_events_total{cache_event_type="miss"}[5m]))
)
```

Notification throughput by channel:

```promql
sum by (channel) (rate(nightwatch_notifications_total[5m]))
```

95th percentile notification duration:

```promql
histogram_quantile(
  0.95,
  sum by (le, channel) (rate(nightwatch_notification_duration_seconds_bucket[5m]))
)
```

## Prometheus Scrape Config Example

```yaml
scrape_configs:
  - job_name: 'laravel-nightwatch-promethus'
    scrape_interval: 15s
    metrics_path: /metrics
    static_configs:
      - targets:
          - your-app.test
```

If your metrics route path is customized in `config/nightwatch-promethus.php`, update `metrics_path` to match.
