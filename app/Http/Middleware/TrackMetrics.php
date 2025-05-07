<?php

namespace App\Http\Middleware;

use App\Services\MetricsService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class TrackMetrics
{
    protected MetricsService $metrics;

    public function __construct(MetricsService $metrics)
    {
        $this->metrics = $metrics;
    }

    public function handle(Request $request, Closure $next): Response
    {
        $start = microtime(true);

        /** @var Response $response */
        $response = $next($request);

        $duration = microtime(true) - $start;

        $route = $request->route() && $request->route()->getName()
                    ? $request->route()->getName()
                    : $request->path();

        $method = $request->method();
        $status = $response->getStatusCode();

        $this->metrics->registerHttpRequestMetric($route, $method, $status, $duration);

        return $response;
    }
}
