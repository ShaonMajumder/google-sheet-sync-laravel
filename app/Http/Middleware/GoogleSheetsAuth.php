<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use ShaonMajumder\Facades\CacheHelper;

class GoogleSheetsAuth
{
    private $redisKey;
    public function __construct()
    {
        $this->redisKey = CacheHelper::getCacheKey('google_sheet_access_token');
    }

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        $hostWithPort = route('home');
        $tokenData = CacheHelper::getCache($this->redisKey);
        if (!$tokenData) {
            return response()->json([
                'status' => false,
                'error' => "To get access visit $hostWithPort to in browser."
            ], 403);
        }

        return $next($request);
    }
}
