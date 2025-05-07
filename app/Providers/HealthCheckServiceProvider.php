<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use ShaonMajumder\MicroserviceUtility\Services\HealthCheckService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Http;

class HealthCheckServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot(HealthCheckService $health)
    {
        $health->register('redis', fn () => Redis::connection()->ping());
        $health->register('mysql', fn () => DB::connection()->getPdo());
        $health->register('elasticsearch', function () {
            $response = Http::get(env('ELK_HOST',));
            return $response->successful();
        });
        $health->register('kibana', function () {
            $response = Http::get(env('KIBANA_HOST'));
            return $response->successful();
        });
        $health->register('logstash', function () {
            $response = Http::get(env('LOGSTASH_HEALTH_URL', 'http://localhost:9600/_node/stats'));
            return $response->successful() && $response->status() === 200 && $response->json('status') === 'green';
        });
        $health->register('grafana', function () {
            $response = Http::get(env('GRAFANA_HOST', 'http://localhost:3000'));
            return $response->successful();
        });
        $health->register('prometheus', function () {
            $response = Http::get(env('PROMETHEUS_HOST', 'http://localhost:9090/-/healthy'));
            return $response->successful();
        });
    }
}
