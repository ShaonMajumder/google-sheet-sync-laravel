<?php

namespace App\Http\Controllers;

use App\Helpers\GoogleSheetHelper;
use Illuminate\Http\Request;
use Exception;
use Google\Client;
use Google\Service\Sheets;
use Illuminate\Support\Facades\Redis;

class GoogleSheetSyncController extends Controller
{
    public function __construct()
    {

    }

    public function oauthCallback(Request $request)
    {
        if ($request->has('code')) {
            try {
                $client = new Client();
                $client->setApplicationName('Google Sheets API PHP Quickstart');
                $client->setScopes(Sheets::SPREADSHEETS);
                $client->setAuthConfig(env('CREDENTIALS_FILE'));
                $client->setAccessType('offline');

                $authCode = $request->input('code');
                
                // Exchange the authorization code for an access token
                $accessToken = $client->fetchAccessTokenWithAuthCode($authCode);
                // Example : $accessToken
                // array:6 [▼
                //     "access_token" => "ya29.a0ARW5m74hIjmOX3v6xxtK_u6HTDEeqCreYEzF-yAmeXItvTa1F3-n3KCcGF1seh9kru9eGE3-GL3JDe4gd2Ns8F2hUOYiEjCN4cauvBsjTX9grYeVxgpJNKLL8LCh9j1Z6B5kg6zdxDs-XgRv5gAfTOcAg ▶"
                //     "expires_in" => 3599
                //     "refresh_token" => "1//0gnUMZXXKgU-KCgYIARAAGBASNwF-L9Irq-ZsM5gXDlXf2SbCvP_-6uqNebKkIeTmxIQzmO0C3MHyM3rIQuSPlS1oXNPp2mh_QJY"
                //     "scope" => "https://www.googleapis.com/auth/spreadsheets"
                //     "token_type" => "Bearer"
                //     "created" => 1737240385
                // ]
                $client->setAccessToken($accessToken);

                if (array_key_exists('error', $accessToken)) {
                    throw new Exception(join(', ', $accessToken));
                }

                $redisTTL = $accessToken['expires_in'];
                $redisKey = 'google_sheet_access_token';
                $redisValue = json_encode($client->getAccessToken());
                Redis::setex($redisKey, $redisTTL, $redisValue);

                $host = $_SERVER['HTTP_HOST'];
                $redirectUrl = 'http://' . $host . '/sheet';
                return view('oauth-success', [
                    'message' => 'Authorization successful, token saved',
                    'redirectUrl' => $redirectUrl,
                    'success' => true
                ]);
            } catch (Exception $e) {
                return view('oauth-success', [
                    'message' => 'Failed to get access token: ' . $e->getMessage(),
                    'success' => false
                ]);
            }
        }

        return response()->json(['error' => 'Authorization code missing'], 400);
    }

    public function getCachedGoogleSheetKey(){
        return json_decode(Redis::get('google_sheet_access_token'), true);
    }

    public function revokeAccessToken(){
        $googleSheets = new GoogleSheetHelper(false);
        return $googleSheets->revokeAccessToken();
    }
    
    public function sync(){
        try {
            $googleSheets = new GoogleSheetHelper();
        
            $spreadsheetTitle = "Robist Spreadsheet";
            $spreadsheetId = $googleSheets->createSpreadsheet($spreadsheetTitle);
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


}
