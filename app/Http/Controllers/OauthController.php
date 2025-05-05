<?php

namespace App\Http\Controllers;

use ShaonMajumder\Facades\CacheHelper;
use App\Helpers\GoogleSheetHelper;
use Exception;
use Illuminate\Http\Request;
use Google\Client;
use Google\Service\Sheets;
use Illuminate\Support\Facades\Redis;

/**
 * @OA\Info(
 *     title="API Documentation",
 *     version="1.0.0",
 *     description="API documentation for our system.",
 *     @OA\Contact(
 *         email="support@example.com"
 *     )
 * )
 */
class OauthController extends Controller
{
    private $redisKey;
    private $oauthApplicationName;
    
    public function __construct()
    {
        $this->oauthApplicationName = config('oauth.application_name');
        $this->redisKey = CacheHelper::getCacheKey('google_sheet_access_token');
    }

    public function landingPage(){
        $tokenData = CacheHelper::getCache($this->redisKey);
        return view('landing_page', [
            'needsSetup' => empty(env('CREDENTIALS_FILE')),
            'tokenData' => $tokenData
        ]);
    }

    public function home(){
        $tokenData = CacheHelper::getCache($this->redisKey);
        return view('oauth.home',compact('tokenData'));
    }
    
    public function ouathAccess(){
        $oauth = new GoogleSheetHelper();
        $oauth->redirectToOauth();
    }

    /**
     * @OA\Get(
     *     path="/google-sheets/api/v0/access-revoke",
     *     summary="Revoke access to Google Sheets",
     *     tags={"Google Sheets"},
     *     @OA\Response(
     *         response=200,
     *         description="Access revoked successfully"
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Invalid request"
     *     )
     * )
     */
    public function revokeAccessToken(){
        $googleSheets = new GoogleSheetHelper(false);
        $status = $googleSheets->revokeAccessToken();
        if($status){
            return response()->json(['message' => 'Access token successfully revoked']);
        } else {
            return response()->json(['error' => 'Failed to revoke access token'], 400);
        }
    }

    public function revokeAccessToken2(){
        $googleSheets = new GoogleSheetHelper(false);
        $status = $googleSheets->revokeAccessToken();
        if($status){
            return redirect()->route('revoke.access')->with('success', 'Access revoked successfully.');
        } else {
            return redirect()->route('revoke.access')->with('error', 'Failed to revoke access.');
        }
    }
    
    public function oauthCallback(Request $request)
    {
        if ($request->has('code')) {
            try {
                $client = new Client();
                $client->setApplicationName($this->oauthApplicationName);
                $client->setScopes(Sheets::SPREADSHEETS);
                $client->setAuthConfig(env('CREDENTIALS_FILE'));
                $client->setAccessType('offline');

                $authCode = $request->input('code');
                
                // Exchange the authorization code for an access token
                $accessToken = $client->fetchAccessTokenWithAuthCode($authCode);
                $client->setAccessToken($accessToken);

                if (array_key_exists('error', $accessToken)) {
                    throw new Exception(join(', ', $accessToken));
                }

                $redisTTL = $accessToken['expires_in'];
                $redisValue = json_encode($client->getAccessToken());
                CacheHelper::setCache($this->redisKey, $redisValue, $redisTTL);
                $bladeVars = [
                    'message' => 'Authorization successful, token saved',
                    'redirectUrl' => route('landing.page'),
                    'success' => true
                ];
            } catch (Exception $e) {
                $bladeVars = [
                    'message' => 'Failed to get access token: ' . $e->getMessage(),
                    'success' => false
                ];
            }
            return view('oauth.oauth-success', $bladeVars);
        }

        return response()->json(['error' => 'Authorization code missing'], 400);
    }

    public function accessTokenRevoke(){
        return view('oauth.revoke');
    }
}
