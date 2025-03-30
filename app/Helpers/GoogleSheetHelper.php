<?php

namespace App\Helpers;

use Exception;
use Google\Client;
use Google\Service\Sheets;
use Google\Service\Sheets\Sheet;
use Illuminate\Support\Facades\Redis;
use GuzzleHttp\Client as GuzzleClient;
use Google\Service\Sheets\ValueRange;
use Illuminate\Support\Facades\Log;
use ShaonMajumder\Facades\CacheHelper;

class GoogleSheetHelper
{
    private $client;
    private $service;
    private $spreadsheetId;
    private $credentialFile;
    private $redisKey;
    private $oauthApplicationName;
    private $driveService;

    public function __construct($initializeClient = true)
    {
        // $this->spreadsheetId = env('SPREADSHEET_ID');
        $this->oauthApplicationName = config('oauth.application_name');
        $this->redisKey = CacheHelper::getCacheKey('google_sheet_access_token');
        $this->credentialFile = env('CREDENTIALS_FILE');
        if ($initializeClient) {
            $this->initializeService();
        }
    }

    public function setSpreadsheetId($spreadsheetId)
    {
        $this->spreadsheetId = $spreadsheetId;
    }

    private function initializeService()
    {
        if (!$this->client || !$this->service) {
            $this->client = $this->getClient();
            $this->service = new Sheets($this->client);
            $this->driveService = new \Google\Service\Drive($this->client);
        }
    }

    public function redirectToOauth(){
        $client = new Client();
        $client->setApplicationName($this->oauthApplicationName);
        $this->setScopes($client);
        $client->setAuthConfig($this->credentialFile);
        $client->setAccessType('offline');

        $authUrl = $client->createAuthUrl();
        header("Location: $authUrl");
        exit();
    }

    public function refreshAccessToken($client){
        $client->fetchAccessTokenWithRefreshToken($client->getRefreshToken());
        $accessToken = $client->getAccessToken();
        $redisValue = json_encode($accessToken);
        $redisTTL = $accessToken['expires_in'];
        
        CacheHelper::setCache($this->redisKey, $redisValue, $redisTTL);
    }

    private function setScopes($client){
        $client->setScopes([
            Sheets::SPREADSHEETS,
            Sheets::DRIVE,
        ]);
    }

    private function getClient()
    {
        $client = new Client();
        $client->setApplicationName($this->oauthApplicationName);
        $this->setScopes($client);
        $client->setAuthConfig($this->credentialFile);
        $client->setAccessType('offline');
        
        $tokenData = CacheHelper::getCache($this->redisKey);
        if ($tokenData) {
            $accessToken = json_decode($tokenData, true);
            $client->setAccessToken($accessToken);
        }
        
        if ($client->isAccessTokenExpired()) {
            if ($client->getRefreshToken()) {
                $this->refreshAccessToken($client);
            } else {
                $this->redirectToOauth();
                // exits
            }
        }

        return $client;
    }

    // methods from standalone library
    public function revokeAccessTokenStandAlone()
    {
        try {
            $tokenData = CacheHelper::getCache($this->redisKey);
            if (!$tokenData) {
                return response()->json(['message' => 'No access token found to revoke'], 404);
            }

            $accessToken = json_decode($tokenData, true)['access_token'];
            $guzzleClient = new GuzzleClient();
            $revokeUrl = 'https://oauth2.googleapis.com/revoke?token=' . $accessToken;
            $response = $guzzleClient->post($revokeUrl, [
                'headers' => ['Content-type' => 'application/x-www-form-urlencoded'],
            ]);

            if ($response->getStatusCode() === 200) {
                CacheHelper::delCache($this->redisKey);
                return response()->json(['message' => 'Access token successfully revoked']);
            }

            return response()->json(['error' => 'Failed to revoke access token'], $response->getStatusCode());
        } catch (Exception $e) {
            return response()->json(['error' => 'Error revoking access token: ' . $e->getMessage()], 500);
        }
    }

    public function revokeAccessToken()
    {
        try {
            $tokenData = CacheHelper::getCache($this->redisKey);
            if (!$tokenData) {
                return response()->json(['message' => 'No access token found to revoke'], 404);
            }

            $accessToken = json_decode($tokenData, true);
            if (!isset($accessToken['access_token'])) {
                return response()->json(['message' => 'Invalid access token data'], 400);
            }

            $client = new \Google\Client();
            $client->setAuthConfig($this->credentialFile);
            $client->setAccessToken($accessToken);
            if ($client->revokeToken()) {
                CacheHelper::delCache($this->redisKey);
                return response()->json(['message' => 'Access token successfully revoked']);
            }

            return response()->json(['error' => 'Failed to revoke access token'], 400);
        } catch (Exception $e) {
            return response()->json(['error' => 'Error revoking access token: ' . $e->getMessage()], 500);
        }
    }

    public function createSpreadsheet($title, $data = null, $sheetName = 'Sheet1')
    {
        try {
            $this->initializeService();
            $spreadsheet = new \Google\Service\Sheets\Spreadsheet([
                'properties' => ['title' => $title]
            ]);

            $response = $this->service->spreadsheets->create($spreadsheet, ['fields' => 'spreadsheetId']);
            $spreadsheetId = $response->spreadsheetId;

            Log::info("Created new spreadsheet with ID: $spreadsheetId");

            if (!$this->sheetExists($spreadsheetId, $sheetName)) {
                $this->createSheet($spreadsheetId, $sheetName);
            }

            if ($data) {
                $this->appendRowToSheet($spreadsheetId, $sheetName, $data);
            }

            return $spreadsheetId;
        } catch (Exception $e) {
            Log::error('An error occurred: ' . $e->getMessage());
            return null;
        }
    }

    public function createSheet($sheetName)
    {
        try {
            $this->initializeService();
            $spreadsheetId = $this->spreadsheetId;
            $requests = [
                'addSheet' => [
                    'properties' => [
                        'title' => $sheetName
                    ]
                ]
            ];

            $body = new Sheets\BatchUpdateSpreadsheetRequest(['requests' => [$requests]]);
            $response = $this->service->spreadsheets->batchUpdate($spreadsheetId, $body);

            $sheetId = $response->replies[0]['addSheet']['properties']['sheetId'];
            Log::info("Sheet '$sheetName' created with ID: $sheetId\n");
            return $sheetId;
        } catch (Exception $e) {
            Log::error('An error occurred: ' . $e->getMessage());
            return null;
        }
    }

    private function sheetExists($spreadsheetId, $sheetName)
    {
        try {
            $this->initializeService();

            // Get the spreadsheet's metadata
            $spreadsheet = $this->service->spreadsheets->get($spreadsheetId);

            // Check if any sheet matches the given name
            foreach ($spreadsheet->getSheets() as $sheet) {
                if ($sheet->getProperties()->getTitle() === $sheetName) {
                    return true;
                }
            }

            return false;
        } catch (Exception $e) {
            Log::error('An error occurred while checking if the sheet exists: ' . $e->getMessage() . "\n");
            return false;
        }
    }

    public function insertData($sheetName, $data)
    {
        try {
            $this->initializeService();
            $range = "$sheetName!A1";
            $body = new Sheets\ValueRange([
                'values' => $data
            ]);

            $params = ['valueInputOption' => 'RAW'];
            $result = $this->service->spreadsheets_values->update($this->spreadsheetId, $range, $body, $params);

            Log::info("Data inserted into sheet '$sheetName'.\n");
        } catch (Exception $e) {
            Log::error('An error occurred: ' . $e->getMessage());
        }
    }

    public function readSheet($sheetName)
    {
        try {
            $this->initializeService();
            $range = $sheetName;
            $response = $this->service->spreadsheets_values->get($this->spreadsheetId, $range);
            $values = $response->getValues();

            return $values ?: [];
        } catch (Exception $e) {
            Log::error('An error occurred: ' . $e->getMessage());
            return null;
        }
    }

    public function appendData($sheetName, $data)
    {
        try {
            $this->initializeService();
            
            $range = "$sheetName"; // Google Sheets will find the next available row
            $body = new \Google\Service\Sheets\ValueRange([
                'values' => $data
            ]);

            $params = [
                'valueInputOption' => 'RAW', // Change to USER_ENTERED if you want formulas processed
                'insertDataOption' => 'INSERT_ROWS' // Appends instead of overwriting
            ];

            $result = $this->service->spreadsheets_values->append($this->spreadsheetId, $range, $body, $params);

            Log::info("Data successfully appended to sheet '$sheetName'.");

            return $result->getUpdates();
        } catch (\Google\Service\Exception $e) {
            Log::error('Google API error: ' . $e->getMessage());
            return false;
        } catch (Exception $e) {
            Log::error('An error occurred: ' . $e->getMessage());
            return false;
        }
    }

    public function appendRowToSheet(string $spreadsheetId, string $sheetName, array $data): void
    {
        try {
            $this->initializeService();

            // Define the range to append data to (e.g., sheet name and starting column)
            $range = $sheetName; // By default, appending to the sheet's end
            $valueRange = new ValueRange([
                'values' => $data
            ]);
            $response = $this->service->spreadsheets_values->append(
                $spreadsheetId,
                $range,
                $valueRange,
                ['valueInputOption' => 'RAW']
            );

            Log::info("Data appended successfully to sheet '$sheetName' in spreadsheet ID '$spreadsheetId'.");

        } catch (Exception $e) {
            Log::error('An error occurred while appending rows: ' . $e->getMessage() . "\n");
        }
    }

    public function appendRow($rowData, $sheetName)
    {
        try {
            $this->initializeService();
            $range = $sheetName;
            $body = new Sheets\ValueRange([
                'values' => [$rowData]
            ]);

            $params = ['valueInputOption' => 'RAW'];
            $response = $this->service->spreadsheets_values->append($this->spreadsheetId, $range, $body, $params);

            return $response;
        } catch (Exception $e) {
            Log::error('An error occurred: ' . $e->getMessage());
            return null;
        }
    }

    public function deleteSpreadsheet($spreadsheetId)
    {
        $result = false;
        try {
            $this->initializeService();

            $response = $this->driveService->files->delete($spreadsheetId);
            if ($response->getStatusCode() === 204) {
                Log::info("Spreadsheet with ID: $spreadsheetId has been deleted successfully.");
                $result = true;
            } elseif ($response->getStatusCode() === 404) {
                Log::error("Spreadsheet with ID: $spreadsheetId not found.");
            }
        } catch (Exception $e) {
            $errorJson = json_decode($e->getMessage(), true);
            if (isset($errorJson['error']['code']) && $errorJson['error']['code'] === 404) {
                Log::error('Error deleting spreadsheet: ' . ($errorJson['error']['message'] ?? ''));
            } else {
                Log::error('Error deleting spreadsheet: ' . $e->getMessage());
            }
        }
        return $result;
    }

    public function deleteSheetById($spreadsheetId, $sheetId)
    {
        try {
            $this->initializeService();

            $requests = [
                new \Google\Service\Sheets\Request([
                    'deleteSheet' => [
                        'sheetId' => $sheetId,
                    ]
                ])
            ];

            $batchUpdateRequest = new \Google\Service\Sheets\BatchUpdateSpreadsheetRequest([
                'requests' => $requests
            ]);
            $response = $this->service->spreadsheets->batchUpdate($spreadsheetId, $batchUpdateRequest);
            Log::info("Sheet with ID: $sheetId has been deleted successfully from spreadsheet ID: $spreadsheetId.");
            return true;
        } catch (Exception $e) {
            // Handle any errors and log them
            Log::error('Error deleting sheet: ' . $e->getMessage());
            return false;
        }
    }

    public function deleteSheetByName($spreadsheetId, $sheetName)
    {
        try {
            // Initialize the Google Sheets API service
            $this->initializeService();

            // Retrieve the spreadsheet to find the sheetId for the given sheetName
            $spreadsheet = $this->service->spreadsheets->get($spreadsheetId);
            
            // Iterate through the sheets to find the sheetId
            $sheetId = null;
            foreach ($spreadsheet->getSheets() as $sheet) {
                if ($sheet->getProperties()->getTitle() === $sheetName) {
                    $sheetId = $sheet->getProperties()->getSheetId();
                    break;
                }
            }

            // If sheet is found, proceed with deletion
            if ($sheetId !== null) {
                // Prepare the request to delete the sheet
                $request = new \Google_Service_Sheets_Request([
                    'deleteSheet' => [
                        'sheetId' => $sheetId
                    ]
                ]);

                // Execute batch update to delete the sheet
                $batchUpdateRequest = new \Google_Service_Sheets_BatchUpdateSpreadsheetRequest([
                    'requests' => [$request]
                ]);
                $this->service->spreadsheets->batchUpdate($spreadsheetId, $batchUpdateRequest);

                Log::info("Sheet with name: $sheetName has been deleted successfully.");
                return true;
            } else {
                Log::error("Sheet with name: $sheetName not found.");
                return false;
            }
        } catch (Exception $e) {
            Log::error('Error deleting sheet: ' . $e->getMessage());
            return false;
        }
    }


}

?>
