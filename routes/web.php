<?php

use App\Http\Controllers\ElasticsearchController;
use App\Http\Controllers\GoogleSheetController;
use App\Http\Controllers\OauthController;
use App\Http\Controllers\SetupController;
use App\Services\MetricsService;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;
use ShaonMajumder\MicroserviceUtility\UninstallMicroserviceUtility;
/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', [OauthController::class, 'landingPage'])->name('landing.page');

Route::prefix('sheet')->group(function () {
    Route::get('/get-access', [OauthController::class, 'home'])->name('home');
    Route::get('/get-access/ouath', [OauthController::class, 'ouathAccess'])->name('get.access');
    Route::get('/get-access/revoke', [OauthController::class, 'accessTokenRevoke'])->name('revoke.access');
    Route::post('/get-access/revoke/delete', [OauthController::class, 'revokeAccessToken2'])->name('revoke.access.delete');
    Route::get('/oauth/callback', [OauthController::class, 'oauthCallback']);
});

Route::get('/setup', [SetupController::class, 'show'])->name('setup.show');
Route::get('/setup/credential-setup-manual', [SetupController::class, 'showManual'])->name('setup.credentials.manual');
Route::post('/setup', [SetupController::class, 'store'])->name('setup.credentials');

Route::prefix('elasticsearch')->group(function () {
    Route::get('/delete-index-form', [ElasticsearchController::class, 'deleteIndexForm'])->name('elasticsearch.delete-index-form');
    Route::post('/delete-elasticsearch-index/{index}', [ElasticsearchController::class, 'deleteIndex'])->name('delete-elasticsearch-index');
});

// prometheus metrics
Route::get('/metrics', function (MetricsService $metricsService) {
    return response($metricsService->expose(), 200)
        ->header('Content-Type', 'text/plain');
});