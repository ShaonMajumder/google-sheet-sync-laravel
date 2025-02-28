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
    Route::get('/get-access', [GoogleSheetSyncController::class, 'home'])->name('home');
    Route::get('/oauth/callback', [GoogleSheetSyncController::class, 'oauthCallback']);
    Route::get('/revoke', [GoogleSheetSyncController::class, 'revoke'])->name('revoke.access');
    Route::get('/get-access-ouath', [GoogleSheetSyncController::class, 'sync'])->name('get.access');
});