<?php

use App\Http\Controllers\GoogleSheetController;
use App\Http\Controllers\OauthController;
use App\Http\Controllers\SetupController;
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

Route::get('/', function () {
    if (empty(env('CREDENTIALS_FILE'))) {
        return view('setup');
    }

    return view('welcome');
});

Route::prefix('sheet')->group(function () {
    Route::get('/get-access', [OauthController::class, 'home'])->name('home');
    Route::get('/get-access/ouath', [OauthController::class, 'ouathAccess'])->name('get.access');
    Route::get('/get-access/revoke', [OauthController::class, 'accessTokenRevoke'])->name('revoke.access');
    Route::get('/oauth/callback', [OauthController::class, 'oauthCallback']);
});

Route::get('/setup', [SetupController::class, 'show'])->name('setup.show');
Route::post('/setup', [SetupController::class, 'store'])->name('setup.credentials');