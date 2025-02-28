<?php

use App\Http\Controllers\GoogleSheetSyncController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::prefix('google-sheets')->name('google-sheets.')->group(function () {
    Route::post('create-spreadsheet', [GoogleSheetSyncController::class, 'createSpreadsheet']);
    Route::post('create-sheet', [GoogleSheetSyncController::class, 'createSheet']);
    Route::post('insert-data', [GoogleSheetSyncController::class, 'insertData']);
    Route::get('read-sheet', [GoogleSheetSyncController::class, 'readSheet']);
    Route::post('append-row', [GoogleSheetSyncController::class, 'appendRow']);
    Route::get('/revoke', [GoogleSheetSyncController::class, 'revokeAccessToken'])->name('revoke.access');
});