<?php

namespace App\Helpers;

use Exception;
use Google\Client;
use Google\Service\Sheets;
use Illuminate\Support\Facades\Redis;
use GuzzleHttp\Client as GuzzleClient;

class GoogleSheetHelper
{
    private $client;
    private $service;
    private $spreadsheetId;
    private $credentialFile;
    private $redisKey;

    public function __construct($initializeClient = true)
    {
        // $this->spreadsheetId = env('SPREADSHEET_ID');
        $this->redisKey = 'google_sheet_access_token';
        $this->credentialFile = env('CREDENTIALS_FILE');
        // $this->service = new Sheets($this->client);
        if ($initializeClient) {
            $this->client = $this->getClient();
            $this->service = new Sheets($this->client);
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
        }
    }

    private function getClient()
    {
        $client = new Client();
        $client->setApplicationName('Google Sheets API PHP Quickstart');
        $client->setScopes(Sheets::SPREADSHEETS);
        $client->setAuthConfig($this->credentialFile);
        $client->setAccessType('offline');
        // $client->setRedirectUri('http://localhost:8000/sheet/oauth/callback');
        
        $tokenData = Redis::get($this->redisKey);
        
        if ($tokenData) {
            $accessToken = json_decode($tokenData, true);
            $client->setAccessToken($accessToken);
        }
        
        if ($client->isAccessTokenExpired()) {
            if ($client->getRefreshToken()) {
                $client->fetchAccessTokenWithRefreshToken($client->getRefreshToken());
            } else {
                $authUrl = $client->createAuthUrl();
                header("Location: $authUrl");
                exit();
                
                printf("Open the following link in your browser:\n%s\n", $authUrl);
                print 'Enter verification code: ';
                $authCode = trim(fgets(STDIN));

                $accessToken = $client->fetchAccessTokenWithAuthCode($authCode);
                $client->setAccessToken($accessToken);

                if (array_key_exists('error', $accessToken)) {
                    throw new Exception(join(', ', $accessToken));
                }
            }

            $accessToken = $client->getAccessToken();
            $redisValue = json_encode($accessToken);
            $redisTTL = $accessToken['expires_in'];
            
            Redis::setex($this->redisKey, $redisTTL, $redisValue);
        }

        return $client;
    }

    // public function revokeAccessToken()
    // {
    //     try {
    //         $tokenData = Redis::get($this->redisKey);
    //         if (!$tokenData) {
    //             return response()->json(['message' => 'No access token found to revoke'], 404);
    //         }

    //         $accessToken = json_decode($tokenData, true)['access_token'];
    //         $guzzleClient = new GuzzleClient();
    //         $revokeUrl = 'https://oauth2.googleapis.com/revoke?token=' . $accessToken;
    //         $response = $guzzleClient->post($revokeUrl, [
    //             'headers' => ['Content-type' => 'application/x-www-form-urlencoded'],
    //         ]);

    //         if ($response->getStatusCode() === 200) {
    //             Redis::del($this->redisKey);
    //             return response()->json(['message' => 'Access token successfully revoked']);
    //         }

    //         return response()->json(['error' => 'Failed to revoke access token'], $response->getStatusCode());
    //     } catch (Exception $e) {
    //         return response()->json(['error' => 'Error revoking access token: ' . $e->getMessage()], 500);
    //     }
    // }

    public function revokeAccessToken()
    {
        try {
            $tokenData = Redis::get($this->redisKey);
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
                Redis::del($this->redisKey);
                return response()->json(['message' => 'Access token successfully revoked']);
            }

            return response()->json(['error' => 'Failed to revoke access token'], 400);
        } catch (Exception $e) {
            return response()->json(['error' => 'Error revoking access token: ' . $e->getMessage()], 500);
        }
    }

    public function createSpreadsheet($title, $data = null)
    {
        try {
            $this->initializeService();
            $spreadsheet = new Sheets\Spreadsheet([
                'properties' => ['title' => $title]
            ]);

            $response = $this->service->spreadsheets->create($spreadsheet, ['fields' => 'spreadsheetId']);
            $spreadsheetId = $response->spreadsheetId;

            echo "Created new spreadsheet with ID: $spreadsheetId\n";

            if ($data) {
                $this->appendRowToSheet($spreadsheetId, $title, $data);
            }

            return $spreadsheetId;
        } catch (Exception $e) {
            echo 'An error occurred: ' . $e->getMessage();
            return null;
        }
    }

    public function createSheet($sheetName, $spreadsheetId = null)
    {
        try {
            $this->initializeService();
            $spreadsheetId = $spreadsheetId ?: $this->spreadsheetId;
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
            echo "Sheet '$sheetName' created with ID: $sheetId\n";
            return $sheetId;
        } catch (Exception $e) {
            echo 'An error occurred: ' . $e->getMessage();
            return null;
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

            echo "Data inserted into sheet '$sheetName'.\n";
        } catch (Exception $e) {
            echo 'An error occurred: ' . $e->getMessage();
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
            echo 'An error occurred: ' . $e->getMessage();
            return null;
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
            echo 'An error occurred: ' . $e->getMessage();
            return null;
        }
    }
}

?>
