<?php

use App\Http\Controllers\GoogleSheetController;
use App\Http\Controllers\OauthController;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;

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

Route::middleware(['microservice-utility.api.key','google.sheets.auth'])->name('google-sheets.')->group(function () {
    Route::post('create-spreadsheet', [GoogleSheetController::class, 'createSpreadsheet']);
    Route::post('create-sheet/{spreadsheetId}/{sheetName}', [GoogleSheetController::class, 'createSheet']);
    Route::delete('delete-spreadsheet/{spreadsheetId}', [GoogleSheetController::class, 'deleteSpreadsheet']);
    Route::delete('delete-sheet/{spreadsheetId}/{sheetName}', [GoogleSheetController::class, 'deleteSheet']);
    Route::post('insert-data/{spreadsheetId}/{sheetName}', [GoogleSheetController::class, 'insertData']);
    Route::post('append-data/{spreadsheetId}/{sheetName}', [GoogleSheetController::class, 'appendData']);
    Route::get('read-sheet/{spreadsheetId}/{sheetName}', [GoogleSheetController::class, 'readSheet']);
    Route::get('access-revoke', [OauthController::class, 'revokeAccessToken'])->name('revoke.access');
});
