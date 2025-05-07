<?php

namespace App\Services;

use Prometheus\CollectorRegistry;
use Prometheus\Storage\Redis;
use Prometheus\RenderTextFormat;

class MetricsService
{
    protected CollectorRegistry $registry;

    public function __construct()
    {
        $adapter = new Redis([
            'host' => 'laravel-redis', // Redis container name in docker-compose
            'port' => 6379,
        ]);

        $this->registry = new CollectorRegistry($adapter);
    }

    public function registerHttpRequestMetric(string $route, string $method, int $statusCode, float $duration): void
    {
        $histogram = $this->registry->getOrRegisterHistogram(
            'app',
            'http_requests_duration_seconds',
            'HTTP request duration in seconds',
            ['route', 'method', 'status']
        );

        $histogram->observe($duration, [$route, $method, $statusCode]);
    }

    public function expose(): string
    {
        $renderer = new RenderTextFormat();
        $metrics = $this->registry->getMetricFamilySamples();
        return $renderer->render($metrics);
    }
}
