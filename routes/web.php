<?php

use App\Http\Controllers\GoogleSheetSyncController;
use Illuminate\Support\Facades\Route;

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
    return view('welcome');
});

Route::prefix('sheet')->group(function () {
    Route::get('/', [GoogleSheetSyncController::class, 'sync']);
    Route::get('/oauth/callback', [GoogleSheetSyncController::class, 'oauthCallback']);
    Route::get('/revoke', [GoogleSheetSyncController::class, 'revokeAccessToken']);
});
