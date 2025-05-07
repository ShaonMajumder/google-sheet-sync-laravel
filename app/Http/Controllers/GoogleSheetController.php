<?php

namespace App\Http\Controllers;

use ShaonMajumder\Facades\CacheHelper;
use App\Helpers\GoogleSheetHelper;
use App\Helpers\LogstashAttributeHelper;
use Illuminate\Http\Request;
use Exception;
use Google\Client;
use Google\Service\Sheets;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;

/**
 * @OA\Info(
 *     title="Google Sheet Sync Laravel API",
 *     version="1.2.0",
 *     description="A Laravel-based API to synchronize data with Google Sheets. This API allows authentication using OAuth 2.0 and supports reading, writing, appending, and managing Google Spreadsheet data.",
 *     @OA\Contact(
 *         email="smazoomder@gmail.com"
 *     )
 * )
 * 
 * @OA\SecurityScheme(
 *     securityScheme="api_key",
 *     type="apiKey",
 *     in="header",
 *     name="X-API-KEY"
 * )
 * 
 */
class GoogleSheetController extends Controller
{
    private $redisKey;
    
    public function __construct()
    {
        $this->redisKey = CacheHelper::getCacheKey('google_sheet_access_token');
    }

    // check
    public function getCachedGoogleSheetKey(){
        return json_decode(CacheHelper::getCache($this->redisKey), true);
    }
    
    // check
    public function sync(){
        try {
            $googleSheets = new GoogleSheetHelper();
        
            $spreadsheetTitle = "Robist Spreadsheet";
            $data = [
                ["Name", "Age", "City"],
                ["Alice", "30", "New York"],
                ["Bob", "25", "Los Angeles"],
                ["Charlie", "35", "Chicago"]
            ];
            $spreadsheetId = $googleSheets->createSpreadsheet($spreadsheetTitle, $data, 'sheetexample');
            dd('here');
            $googleSheets->setSpreadsheetId($spreadsheetId);
        
            if ($spreadsheetId) {
                echo "Spreadsheet created successfully with ID: $spreadsheetId</br>";
        
                $sheetName = "RobistSampleSheet";
                $sheetId = $googleSheets->createSheet($sheetName, $spreadsheetId);
        
                if ($sheetId) {
                    echo "Sheet '$sheetName' created successfully with ID: $sheetId</br>";
        
                    $data = [
                        ["Name", "Age", "City"],
                        ["Alice", "30", "New York"],
                        ["Bob", "25", "Los Angeles"],
                        ["Charlie", "35", "Chicago"]
                    ];
        
                    $googleSheets->insertData($sheetName, $data);
                    echo "Data inserted successfully into sheet '$sheetName'.</br>";
        
                    $readData = $googleSheets->readSheet($sheetName);
                    echo "Data in '$sheetName':</br>";
                    print_r($readData);
        
                    $newRow = ["Diana", "28", "Houston"];
                    $googleSheets->appendRow($newRow, $sheetName);
                    echo "Row appended successfully to sheet '$sheetName'.</br>";
        
                    $updatedData = $googleSheets->readSheet($sheetName);
                    echo "Updated data in '$sheetName':</br>";
                    print_r($updatedData);
                }
            }
        } catch (Exception $e) {
            echo "An error occurred: " . $e->getMessage();
        }
    }

     /**
     * @OA\Post(
     *     path="/google-sheets/api/v0/create-spreadsheet",
     *     summary="Create a new Google Spreadsheet",
     *     tags={"GoogleSheet"},
     *     security={{"api_key":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         description="Spreadsheet creation payload",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(
     *                 property="title",
     *                 type="string",
     *                 example="Swagger test"
     *             ),
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 @OA\Items(
     *                     type="array",
     *                     @OA\Items(type="string")
     *                 ),
     *                 example={
     *                     {"Name", "Age", "City"},
     *                     {"Alice", "30", "New York"},
     *                     {"Bob", "25", "Los Angeles"}
     *                 }
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Spreadsheet created successfully",
     *         @OA\JsonContent(
     *             @OA\Property(
     *                 property="spreadsheetId",
     *                 type="string",
     *                 example="1x2y3z..."
     *             ),
     *             @OA\Property(
     *                 property="spreadsheetUrl",
     *                 type="string",
     *                 example="https://docs.google.com/spreadsheets/d/1x2y3z..."
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Error occurred while creating spreadsheet",
     *         @OA\JsonContent(
     *             @OA\Property(
     *                 property="error",
     *                 type="string",
     *                 example="Failed to create spreadsheet"
     *             )
     *         )
     *     )
     * )
     */
    public function createSpreadsheet(Request $request): JsonResponse
    {
        try {
            $title = $request->input('title');
            $data = $request->input('data', null);
            $googleSheets = new GoogleSheetHelper();
            $spreadsheetId = $googleSheets->createSpreadsheet($title, $data);

            LogstashAttributeHelper::setAttributes($request, [
                'logMessage' => 'Spreadsheet created successfully',
                'logContext' => [
                    'status' => true,
                    'message' => 'Spreadsheet created successfully',
                    'spreadsheetId' => $spreadsheetId,
                    'link' => "https://docs.google.com/spreadsheets/d/$spreadsheetId"
                ]
            ]);

            // Log::channel('elasticsearch')->info( 'Spreadsheet created successfully', [
            //     'logMessage' => 'Spreadsheet created successfully',
            //     'logContext' => [
            //         'status' => true,
            //         'message' => 'Spreadsheet created successfully',
            //         'spreadsheetId' => $spreadsheetId,
            //         'link' => "https://docs.google.com/spreadsheets/d/$spreadsheetId"
            //     ]
            // ]);

            return response()->json([
                'message' => 'Spreadsheet created successfully',
                'spreadsheetId' => $spreadsheetId,
                'link' => "https://docs.google.com/spreadsheets/d/$spreadsheetId"
            ]);
        } catch (Exception $e) {
            LogstashAttributeHelper::setAttributes($request, [
                'logMessage' => 'Spreadsheet can not be created',
                'logContext' => [
                    'status' => false,
                    'message' => 'Spreadsheet can not be created',
                    'error' => $e->getMessage(),
                    'errorCode' => $e->getCode(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                    'trace' => $e->getTraceAsString(),
                    // 'request' => $request->all(),
                    // 'requestMethod' => $request->method(),
                    // 'requestUrl' => $request->url(),
                    // 'requestIp' => $request->ip(),
                    // 'requestUserAgent' => $request->userAgent(),
                    // 'requestHeaders' => $request->headers->all(),
                    // 'requestCookies' => $request->cookies->all(),
                    // 'requestSession' => $request->session()->all(),
                ]
            ]);

            return response()->json([
                'error' => 'An error occurred: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * @OA\Post(
     *     path="/google-sheets/api/v0/create-sheet/{spreadsheetId}/{sheetName}",
     *     summary="Create a new sheet in the spreadsheet",
     *     tags={"GoogleSheet"},
     *     security={{"api_key":{}}},
     *     @OA\Parameter(
     *         name="spreadsheetId",
     *         in="path",
     *         description="ID of the spreadsheet where the new sheet will be created",
     *         required=true,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="sheetName",
     *         in="path",
     *         description="Name of the new sheet to be created",
     *         required=true,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\RequestBody(
     *         required=false,
     *         description="Optional sheet title and data to populate",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(
     *                 property="title",
     *                 type="string",
     *                 example="Shaon New Spreadsheet"
     *             ),
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 @OA\Items(
     *                     type="array",
     *                     @OA\Items(type="string")
     *                 ),
     *                 example={
     *                     {"Name", "Age", "City"},
     *                     {"Alice", "30", "New York"},
     *                     {"Bob", "25", "Los Angeles"}
     *                 }
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Sheet created successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="sheetId", type="integer", example=1289357324),
     *             @OA\Property(property="message", type="string", example="Sheet 'swagger-sheet-1' created successfully.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Error occurred while creating the sheet",
     *         @OA\JsonContent(
     *             @OA\Property(
     *                 property="error",
     *                 type="string",
     *                 example="Failed to create sheet"
     *             )
     *         )
     *     )
     * )
     */
    public function createSheet(Request $request, $spreadsheetId, $sheetName): JsonResponse
    {
        try {
            $data = $request->input('data', null);
            $googleSheets = new GoogleSheetHelper();
            $googleSheets->setSpreadsheetId($spreadsheetId);
            $sheetId = $googleSheets->createSheet($sheetName, $data);

            return response()->json([
                'sheetId' => $sheetId,
                'message' => "Sheet '$sheetName' created successfully."
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'error' => 'An error occurred: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * @OA\Delete(
     *     path="/google-sheets/api/v0/delete-spreadsheet/{spreadsheetId}",
     *     summary="Delete a specific spreadsheet",
     *     description="Deletes a Google Spreadsheet by its ID.",
     *     operationId="deleteSpreadsheet",
     *     tags={"GoogleSheet"},
     *     security={{"api_key":{}}},
     *     @OA\Parameter(
     *         name="spreadsheetId",
     *         in="path",
     *         description="The ID of the spreadsheet to delete.",
     *         required=true,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Spreadsheet successfully deleted",
     *         @OA\JsonContent(
     *             @OA\Property(
     *                 property="message",
     *                 type="string",
     *                 example="Spreadsheet successfully deleted"
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Spreadsheet not found or could not be deleted",
     *         @OA\JsonContent(
     *             @OA\Property(
     *                 property="error",
     *                 type="string",
     *                 example="Spreadsheet not found or could not be deleted"
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Server error",
     *         @OA\JsonContent(
     *             @OA\Property(
     *                 property="error",
     *                 type="string",
     *                 example="Failed to delete spreadsheet"
     *             ),
     *             @OA\Property(
     *                 property="details",
     *                 type="string",
     *                 example="Error details"
     *             )
     *         )
     *     )
     * )
     */
    public function deleteSpreadsheet($spreadsheetId): JsonResponse
    {
        try {
            $googleSheets = new GoogleSheetHelper();
            $result = $googleSheets->deleteSpreadsheet($spreadsheetId);
            if ($result) {
                return response()->json(['message' => 'Spreadsheet successfully deleted'], 200);
            }
            return response()->json(['error' => 'Spreadsheet not found or could not be deleted'], 404);
        } catch (Exception $e) {
            return response()->json(['error' => 'Failed to delete spreadsheet', 'details' => $e->getMessage()], 500);
        }
    }

    /**
     * @OA\Delete(
     *     path="/google-sheets/api/v0/delete-sheet/{spreadsheetId}/{sheetName}",
     *     summary="Delete a specific sheet in a Google Spreadsheet",
     *     description="Deletes a sheet in the specified Google Spreadsheet by its name.",
     *     operationId="deleteSheet",
     *     tags={"GoogleSheet"},
     *     security={{"api_key":{}}},
     *     @OA\Parameter(
     *         name="spreadsheetId",
     *         in="path",
     *         description="The ID of the spreadsheet from which the sheet will be deleted.",
     *         required=true,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="sheetName",
     *         in="path",
     *         description="The name of the sheet to delete.",
     *         required=true,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Sheet successfully deleted",
     *         @OA\JsonContent(
     *             @OA\Property(
     *                 property="message",
     *                 type="string",
     *                 example="Sheet successfully deleted"
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Sheet not found or could not be deleted",
     *         @OA\JsonContent(
     *             @OA\Property(
     *                 property="error",
     *                 type="string",
     *                 example="Sheet not found or could not be deleted"
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Server error",
     *         @OA\JsonContent(
     *             @OA\Property(
     *                 property="error",
     *                 type="string",
     *                 example="Failed to delete sheet"
     *             ),
     *             @OA\Property(
     *                 property="details",
     *                 type="string",
     *                 example="Error details"
     *             )
     *         )
     *     )
     * )
     */
    public function deleteSheet($spreadsheetId, $sheetName): JsonResponse
    {
        try {
            $googleSheets = new GoogleSheetHelper();
            $result = $googleSheets->deleteSheetByName($spreadsheetId,$sheetName);
            if ($result) {
                return response()->json(['message' => 'Sheet successfully deleted'], 200);
            }
            return response()->json(['error' => 'Sheet not found or could not be deleted'], 404);
        } catch (Exception $e) {
            return response()->json(['error' => 'Failed to delete sheet', 'details' => $e->getMessage()], 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/google-sheets/api/v0/read-sheet/{spreadsheetId}/{sheetName}",
     *     summary="Read data from a specific sheet in a Google Spreadsheet",
     *     description="Fetches the data from the specified sheet in a Google Spreadsheet.",
     *     operationId="readSheet",
     *     tags={"GoogleSheet"},
     *     security={{"api_key":{}}},
     *     @OA\Parameter(
     *         name="spreadsheetId",
     *         in="path",
     *         description="The ID of the spreadsheet to read data from.",
     *         required=true,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="sheetName",
     *         in="path",
     *         description="The name of the sheet to read data from.",
     *         required=true,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Data fetched successfully",
     *         @OA\JsonContent(
     *             @OA\Property(
     *                 property="message",
     *                 type="string",
     *                 example="Data fetched successfully from 'Sheet1'."
     *             ),
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 @OA\Items(
     *                     type="array",
     *                     @OA\Items(type="string")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Server error",
     *         @OA\JsonContent(
     *             @OA\Property(
     *                 property="error",
     *                 type="string",
     *                 example="An error occurred: Error message"
     *             )
     *         )
     *     )
     * )
     */
    public function readSheet($spreadsheetId, $sheetName): JsonResponse
    {
        try {
            $googleSheets = new GoogleSheetHelper();
            $googleSheets->setSpreadsheetId($spreadsheetId);
            $data = $googleSheets->readSheet($sheetName);

            return response()->json([
                'message' => "Data fetched successfully from '$sheetName'.",
                'data' => $data
            ]);
        } catch (Exception $e) {
            return response()->json([
                'error' => 'An error occurred: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * @OA\Post(
     *     path="/google-sheets/api/v0/insert-data/{spreadsheetId}/{sheetName}",
     *     summary="Insert data into a specific sheet in a Google Spreadsheet",
     *     description="Inserts data into the specified sheet of a Google Spreadsheet.",
     *     operationId="insertData",
     *     tags={"GoogleSheet"},
     *     security={{"api_key":{}}},
     *     @OA\Parameter(
     *         name="spreadsheetId",
     *         in="path",
     *         description="The ID of the spreadsheet to insert data into.",
     *         required=true,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="sheetName",
     *         in="path",
     *         description="The name of the sheet to insert data into.",
     *         required=true,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         description="Insert data payload",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 @OA\Items(
     *                     type="array",
     *                     @OA\Items(type="string")
     *                 ),
     *                 example={
     *                      {"ProductID", "Product Name", "Category", "Price", "Stock"},
     *                      {"P001", "Lapto2p", "Electronics", "1200", "50"},
     *                      {"P002", "Smartphone", "Electronics", "800", "100"},
     *                      {"P003", "Desk Chair", "Furniture", "150", "30"}
     *                 }
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Data inserted successfully",
     *         @OA\JsonContent(
     *             @OA\Property(
     *                 property="message",
     *                 type="string",
     *                 example="Data inserted successfully into sheet 'Sheet1'."
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Bad Request – Invalid input format",
     *         @OA\JsonContent(
     *             @OA\Property(property="error", type="string", example="Invalid data format")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Not Found – Spreadsheet or Sheet does not exist",
     *         @OA\JsonContent(
     *             @OA\Property(property="error", type="string", example="Sheet not found")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized – Missing or invalid API key",
     *         @OA\JsonContent(
     *             @OA\Property(property="error", type="string", example="Unauthorized")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Server error",
     *         @OA\JsonContent(
     *             @OA\Property(
     *                 property="error",
     *                 type="string",
     *                 example="An error occurred: Error message"
     *             )
     *         )
     *     )
     * )
     */
    public function insertData(Request $request, $spreadsheetId, $sheetName): JsonResponse
    {
        try {
            $data = $request->input('data');
            $googleSheets = new GoogleSheetHelper();
            $googleSheets->setSpreadsheetId($spreadsheetId);
            $googleSheets->insertData($sheetName, $data);

            return response()->json([
                'message' => "Data inserted successfully into sheet '$sheetName'."
            ]);
        } catch (Exception $e) {
            return response()->json([
                'error' => 'An error occurred: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * @OA\Post(
     *     path="/google-sheets/api/v0/append-data/{spreadsheetId}/{sheetName}",
     *     summary="Append data to a specific sheet in a Google Spreadsheet",
     *     description="Appends rows of data to the specified sheet in a Google Spreadsheet.",
     *     operationId="appendData",
     *     tags={"GoogleSheet"},
     *     security={{"api_key":{}}},
     *     @OA\Parameter(
     *         name="spreadsheetId",
     *         in="path",
     *         description="The ID of the spreadsheet to append data to.",
     *         required=true,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="sheetName",
     *         in="path",
     *         description="The name of the sheet to append data to.",
     *         required=true,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         description="Insert data payload",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 @OA\Items(
     *                     type="array",
     *                     @OA\Items(type="string")
     *                 ),
     *                 example={
     *                      {"ProductID", "Product Name", "Category", "Price", "Stock"},
     *                      {"P001", "Lapto2p", "Electronics", "1200", "50"},
     *                      {"P002", "Smartphone", "Electronics", "800", "100"},
     *                      {"P003", "Desk Chair", "Furniture", "150", "30"}
     *                 }
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Row(s) appended successfully",
     *         @OA\JsonContent(
     *             @OA\Property(
     *                 property="message",
     *                 type="string",
     *                 example="Row appended successfully to sheet 'Sheet1'."
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Bad Request – Invalid data",
     *         @OA\JsonContent(
     *             @OA\Property(property="error", type="string", example="Invalid input format.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized – Missing or invalid API key",
     *         @OA\JsonContent(
     *             @OA\Property(property="error", type="string", example="Unauthorized.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Not Found – Spreadsheet or Sheet does not exist",
     *         @OA\JsonContent(
     *             @OA\Property(property="error", type="string", example="Sheet not found.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Server Error",
     *         @OA\JsonContent(
     *             @OA\Property(property="error", type="string", example="An error occurred: [error message]")
     *         )
     *     )
     * )
     */
    public function appendData(Request $request, $spreadsheetId, $sheetName): JsonResponse
    {
        try {
            $data = $request->input('data');
            $googleSheets = new GoogleSheetHelper();
            $googleSheets->setSpreadsheetId($spreadsheetId);
            $googleSheets->appendData($sheetName, $data);

            return response()->json([
                'message' => "Row appended successfully to sheet '$sheetName'."
            ]);
        } catch (Exception $e) {
            return response()->json([
                'error' => 'An error occurred: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/google-sheets/api/v0/list-spreadsheets",
     *     tags={"Google Sheets"},
     *     summary="List all accessible Google Spreadsheets",
     *     description="Returns a list of spreadsheet names and IDs that the authenticated user has access to.",
     *     operationId="listSpreadsheets",
     *     tags={"GoogleSheet"},
     *     security={{"api_key":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Successful response",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Data fetched successfully."),
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 @OA\Items(
     *                     type="object",
     *                     @OA\Property(property="id", type="string", example="1xflMe941GgXbQOqtud91fbzTHo8A3_nXO2D7dwvYypU"),
     *                     @OA\Property(property="name", type="string", example="To-do list")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Server error",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="boolean", example=false),
     *             @OA\Property(property="error", type="string", example="An error occurred: Something went wrong")
     *         )
     *     )
     * )
     */
    public function listSpreadsheets(): JsonResponse
    {
        try {
            $googleSheets = new GoogleSheetHelper();
            $spreadsheets = $googleSheets->listSpreadsheets();

            return response()->json([
                'status' => true,
                'message' => "Data fetched successfully.",
                'data' => $spreadsheets
            ]);
        } catch (Exception $e) {
            return response()->json([
                'status' => false,
                'error' => 'An error occurred: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/google-sheets/api/v0/list-sheets/{spreadsheetId}",
     *     tags={"Google Sheets"},
     *     summary="List sheets in a spreadsheet",
     *     description="Fetches the names of all sheets within a specific spreadsheet using the given spreadsheet ID.",
     *     operationId="listSheets",
     *     tags={"GoogleSheet"},
     *     security={{"api_key":{}}},
     *     @OA\Parameter(
     *         name="spreadsheetId",
     *         in="path",
     *         required=true,
     *         description="The unique ID of the Google Spreadsheet",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Sheet names fetched successfully.",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Sheet names fetched successfully."),
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 @OA\Items(
     *                     type="object",
     *                     @OA\Property(property="id", type="integer", example=0),
     *                      @OA\Property(property="title", type="string", example="Sheet1")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="An unexpected error occurred.",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="boolean", example=false),
     *             @OA\Property(property="error", type="string", example="An error occurred: Invalid spreadsheet ID.")
     *         )
     *     )
     * )
     */
    public function listSheets($spreadsheetId): JsonResponse
    {
        try {
            $googleSheets = new GoogleSheetHelper();
            $spreadsheets = $googleSheets->listSheets($spreadsheetId);
            return response()->json([
                'status' => true,
                'message' => "Sheet names fetched successfully.",
                'data' => $spreadsheets
            ]);
        } catch (Exception $e) {
            return response()->json([
                'status' => false,
                'error' => 'An error occurred: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/google-sheets/api/v0/sheet-exists/{spreadsheetId}/{sheetName}",
     *     tags={"Google Sheets"},
     *     summary="Check if a spreadsheet or a specific sheet exists",
     *     description="Checks whether a spreadsheet exists by ID, and optionally verifies the existence of a specific sheet name within it.",
     *     operationId="sheetExists",
     *     tags={"GoogleSheet"},
     *     security={{"api_key":{}}},
     *     @OA\Parameter(
     *         name="spreadsheetId",
     *         in="path",
     *         required=true,
     *         description="The ID of the Google Spreadsheet",
     *         @OA\Schema(type="string", example="17d_LsLoqo6939eMZ1dwrsAypdODcJvTsOmfipmJXYKw")
     *     ),
     *     @OA\Parameter(
     *         name="sheetName",
     *         in="path",
     *         required=false,
     *         description="The name of the sheet to check (optional)",
     *         @OA\Schema(type="string", example="Sheet1")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Sheet or spreadsheet found.",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Sheet 'Sheet1' found."),
     *             @OA\Property(property="data", type="array", @OA\Items(type="object"))
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Sheet or spreadsheet not found, or an error occurred.",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Sheet 'Sheet1' not found."),
     *             @OA\Property(property="error", type="string", example="An error occurred: Invalid spreadsheet ID.")
     *         )
     *     )
     * )
     */
    public function sheetExists($spreadsheetId, $sheetName=null): JsonResponse
    {
        try {
            $googleSheets = new GoogleSheetHelper();
            $spreadsheets = $googleSheets->sheetExists($spreadsheetId, $sheetName);
            if($spreadsheets){
                return response()->json([
                    'status' => true,
                    'message' => $sheetName ? "Sheet '$sheetName' found." : "Spreadsheet '$spreadsheetId' found.",
                    'data' => []
                ], 200);
            }
            return response()->json([
                'status' => false,
                'message' => $sheetName ? "Sheet '$sheetName' not found." : "Spreadsheet '$spreadsheetId' not found.",
                'data' => []
            ], 500);
        } catch (Exception $e) {
            return response()->json([
                'status' => false,
                'message' => $sheetName ? "Sheet '$sheetName' not found." : "Spreadsheet '$spreadsheetId' not found.",
                'error' => 'An error occurred: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * @OA\Post(
     *     path="/google-sheets/api/v0/find-value/{spreadsheetId}/{sheetName}",
     *     tags={"GoogleSheet"},
     *     summary="Find a value in a specific sheet",
     *     description="Searches for a specific value in a given sheet of a Google Spreadsheet.",
     *     operationId="findValueInSheet",
     *     security={{"api_key":{}}},
     *     @OA\Parameter(
     *         name="spreadsheetId",
     *         in="path",
     *         required=true,
     *         description="ID of the Google Spreadsheet",
     *         @OA\Schema(type="string", example="17d_LsLoqo6939eMZ1dwrsAypdODcJvTsOmfipmJXYKw")
     *     ),
     *     @OA\Parameter(
     *         name="sheetName",
     *         in="path",
     *         required=true,
     *         description="Name of the sheet to search in",
     *         @OA\Schema(type="string", example="Sheet1")
     *     ),
     *     @OA\Parameter(
     *         name="search",
     *         in="query",
     *         required=true,
     *         description="The value to search for in the sheet",
     *         @OA\Schema(type="string", example="Name")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Value found successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Value Found"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(
     *                     property="position",
     *                     type="object",
     *                     @OA\Property(property="row", type="integer", example=1),
     *                     @OA\Property(property="column", type="string", example="A")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Search parameter not found or other error occurred",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Search parameter not found"),
     *             @OA\Property(property="error", type="string", example="An error occurred: Sheet not accessible")
     *         )
     *     )
     * )
     */
    public function findValue(Request $request, $spreadsheetId, $sheetName): JsonResponse
    {
        try {
            $search = $request->input('search', null);
            $googleSheets = new GoogleSheetHelper();
            $googleSheets->setSpreadsheetId($spreadsheetId);
            $position = $googleSheets->findValue($sheetName, $search);
            if($position){
                return response()->json([
                    'status' => true,
                    'message' => "Value Found",
                    'data' => [
                        'position' => $position
                    ]
                ], 200);
            }
            return response()->json([
                'status' => false,
                'message' => "Search parameter not found",
                'data' => []
            ], 500);
        } catch (Exception $e) {
            return response()->json([
                'status' => false,
                'message' => "Search parameter not found",
                'error' => 'An error occurred: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/google-sheets/api/v0/get-sheet-metadata/{spreadsheetId}/{sheetName}",
     *     tags={"GoogleSheet"},
     *     summary="Get metadata of a specific sheet",
     *     description="Fetches metadata for a specified sheet within a Google Spreadsheet.",
     *     operationId="getSheetMetadata",
     *     security={{"api_key":{}}},
     *     @OA\Parameter(
     *         name="spreadsheetId",
     *         in="path",
     *         required=true,
     *         description="ID of the Google Spreadsheet",
     *         @OA\Schema(type="string", example="17d_LsLoqo6939eMZ1dwrsAypdODcJvTsOmfipmJXYKw")
     *     ),
     *     @OA\Parameter(
     *         name="sheetName",
     *         in="path",
     *         required=true,
     *         description="Name of the sheet to get metadata for",
     *         @OA\Schema(type="string", example="Sheet1")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Sheet metadata retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Metadata populated"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="hidden", type="boolean", nullable=true),
     *                 @OA\Property(property="index", type="integer", example=0),
     *                 @OA\Property(property="rightToLeft", type="boolean", nullable=true),
     *                 @OA\Property(property="sheetId", type="integer", example=0),
     *                 @OA\Property(property="sheetType", type="string", example="GRID"),
     *                 @OA\Property(property="title", type="string", example="Sheet1"),
     *                 @OA\Property(
     *                     property="gridProperties",
     *                     type="object",
     *                     @OA\Property(property="columnCount", type="integer", example=26),
     *                     @OA\Property(property="rowCount", type="integer", example=1000),
     *                     @OA\Property(property="columnGroupControlAfter", type="integer", nullable=true),
     *                     @OA\Property(property="rowGroupControlAfter", type="integer", nullable=true),
     *                     @OA\Property(property="frozenColumnCount", type="integer", nullable=true),
     *                     @OA\Property(property="frozenRowCount", type="integer", nullable=true),
     *                     @OA\Property(property="hideGridlines", type="boolean", nullable=true)
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Metadata not found or an error occurred",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Metadata not found"),
     *             @OA\Property(property="error", type="string", example="An error occurred: Sheet not found")
     *         )
     *     )
     * )
     */
    public function getSheetMetadata($spreadsheetId, $sheetName=null): JsonResponse
    {
        try {
            $googleSheets = new GoogleSheetHelper();
            $googleSheets->setSpreadsheetId($spreadsheetId);
            $result = $googleSheets->getSheetMetadata($spreadsheetId, $sheetName);
            if($result){
                return response()->json([
                    'status' => true,
                    'message' => "Metadata populated",
                    'data' => $result
                ], 200);
            }
            return response()->json([
                'status' => false,
                'message' => "Metadata not found",
                'data' => []
            ], 500);
        } catch (Exception $e) {
            return response()->json([
                'status' => false,
                'message' => "Metadata not found",
                'error' => 'An error occurred: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * @OA\Get(
     *     path="/google-sheets/api/v0/clear-sheet/{spreadsheetId}/{sheetName}",
     *     tags={"GoogleSheet"},
     *     summary="Clear all data from a sheet",
     *     description="Clears all values in the specified sheet of a Google Spreadsheet.",
     *     operationId="clearSheet",
     *     security={{"api_key":{}}},
     *     @OA\Parameter(
     *         name="spreadsheetId",
     *         in="path",
     *         required=true,
     *         description="ID of the Google Spreadsheet",
     *         @OA\Schema(type="string", example="17d_LsLoqo6939eMZ1dwrsAypdODcJvTsOmfipmJXYKw")
     *     ),
     *     @OA\Parameter(
     *         name="sheetName",
     *         in="path",
     *         required=true,
     *         description="Name of the sheet to clear",
     *         @OA\Schema(type="string", example="Sheet1")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Sheet cleared successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Sheet 'Sheet1' cleared successfully."),
     *             @OA\Property(property="data", type="boolean", example=true)
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Failed to clear the sheet",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Failed to clear sheet 'Sheet1'."),
     *             @OA\Property(property="error", type="string", example="An error occurred: Sheet not found"),
     *             @OA\Property(property="data", type="array", @OA\Items())
     *         )
     *     )
     * )
     */
    public function clearSheet($spreadsheetId, $sheetName): JsonResponse
    {
        try {
            $googleSheets = new GoogleSheetHelper();
            $googleSheets->setSpreadsheetId($spreadsheetId);
            $result = $googleSheets->clearSheet($sheetName);
            if ($result) {
                return response()->json([
                    'status' => true,
                    'message' => "Sheet '$sheetName' cleared successfully.",
                    'data' => $result
                ], 200);
            }

            return response()->json([
                'status' => false,
                'message' => "Failed to clear sheet '$sheetName'.",
                'data' => []
            ], 500);
        } catch (Exception $e) {
            return response()->json([
                'status' => false,
                'message' => "Failed to clear sheet '$sheetName'.",
                'error' => 'An error occurred: ' . $e->getMessage(),
                'data' => []
            ], 500);
        }
    }

    
}
