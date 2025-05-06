<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class LogstashMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        $start = microtime(true);
        $response = $next($request);
        $duration = round((microtime(true) - $start) * 1000, 2); // in ms

        if ($request->attributes->has('logMessage')) {
            $logMessage = $request->attributes->get('logMessage');
            $logContext = $request->attributes->get('logContext', []);

            $logContext = array_merge($logContext, [
                'status' => $response->status(),
                'httpStatusCode' => $response->status(),
                'method' => $request->method(),
                'url' => $request->fullUrl(),
                'ip' => $request->ip(),
                'userAgent' => $request->userAgent(),
                'responseTimeMs' => $duration,
            ]);
            
            Log::channel('elasticsearch')->info($logMessage, $logContext);
        }

        return $response;
    }
}