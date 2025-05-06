<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use ShaonMajumder\MicroserviceUtility\Services\HealthCheckService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;

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
        $health->register('db', fn () => DB::connection()->getPdo());
    }
}
