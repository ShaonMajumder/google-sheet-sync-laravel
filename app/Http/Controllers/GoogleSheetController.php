<?php

namespace App\Http\Controllers;

use ShaonMajumder\Facades\CacheHelper;
use App\Helpers\GoogleSheetHelper;
use Illuminate\Http\Request;
use Exception;
use Google\Client;
use Google\Service\Sheets;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;


use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;


class GoogleSheetController extends Controller
{
    private $redisKey;
    
    public function __construct()
    {
        $this->redisKey = CacheHelper::getCacheKey('google_sheet_access_token');
    }

    private function restartApp()
    {
        $commands = [
            'php artisan down',
            'php artisan queue:restart',
            'php artisan config:clear',
            'php artisan cache:clear',
            'php artisan up'
        ];

        foreach ($commands as $command) {
            $process = Process::fromShellCommandline($command);
            $process->run();

            if (!$process->isSuccessful()) {
                throw new ProcessFailedException($process);
            }

            $this->info("Executed: $command");
        }
    }

    public function getCachedGoogleSheetKey(){
        return json_decode(CacheHelper::getCache($this->redisKey), true);
    }
    
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

    public function createSpreadsheet(Request $request)
    {
        try {
            $title = $request->input('title');
            $data = $request->input('data', null);
            $googleSheets = new GoogleSheetHelper();
            $spreadsheetId = $googleSheets->createSpreadsheet($title, $data);

            return response()->json([
                'message' => 'Spreadsheet created successfully',
                'spreadsheetId' => $spreadsheetId
            ]);
        } catch (Exception $e) {
            return response()->json([
                'error' => 'An error occurred: ' . $e->getMessage()
            ], 500);
        }
    }

    public function createSheet($spreadsheetId, $sheetName)
    {
        try {
            $googleSheets = new GoogleSheetHelper();
            $googleSheets->setSpreadsheetId($spreadsheetId);
            $sheetId = $googleSheets->createSheet($sheetName);

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

    public function deleteSpreadsheet($spreadsheetId){
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

    public function deleteSheet($spreadsheetId, $sheetName){
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

    public function insertData(Request $request, $spreadsheetId, $sheetName)
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

    public function appendData(Request $request, $spreadsheetId, $sheetName)
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

    public function readSheet($spreadsheetId, $sheetName)
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
}
