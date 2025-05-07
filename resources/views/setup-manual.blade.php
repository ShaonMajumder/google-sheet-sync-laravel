@extends('layouts.authorization')

@section('title', 'Google Sheet Setup')

@section('content')
    <div class="mt-6 space-y-4">
        <div class="p-4 mb-4 bg-blue-50 border border-blue-200 rounded-lg text-sm text-blue-900 break-words overflow-auto">
            <strong>🔐 Google OAuth Setup for Laravel Google Sheet Sync</strong>
            <ol class="list-decimal pl-5 mt-2 space-y-2 text-sm">
                <li>
                    <strong>Go to Google Cloud Console</strong><br>
                    Open: <a href="https://console.cloud.google.com/" target="_blank" class="text-blue-600 underline">Google Cloud Console</a>
                </li>
                <li>enable Sheets + Drive APIs</li>
                <li>
                    <strong>Navigate to APIs & Services → Credentials</strong><br>
                    <a href="https://console.cloud.google.com/apis/credentials" target="_blank" class="text-blue-600 underline">Credentials Page</a>
                </li>
                <li>
                    <strong>Download OAuth 2.0 Credentials JSON</strong><br>
                    Locate your existing credential under <em>"OAuth 2.0 Client IDs"</em> (e.g., <code>Web client 1 Laravel Google Sheet Sync</code>)<br>
                    Click the <strong>download icon</strong> beside it and save the file:<br>
                    <code>E:\Projects\google-sheet-sync-laravel\storage\client_secret_xxxxx.json</code>
                </li>
                <li>
                    <strong>Copy the credentials file into the Docker container</strong>
                    <pre class="bg-gray-100 p-2 rounded text-xs whitespace-pre-wrap">docker cp "E:\Projects\google-sheet-sync-laravel\storage\client_secret_xxxxx.json" googlesheet-laravel-app:/var/www/html/storage/</pre>
                </li>
                <li>
                    <strong>Update <code>.env</code></strong>
                    <pre class="bg-gray-100 p-2 rounded text-xs whitespace-pre-wrap">CREDENTIALS_FILE=../storage/client_secret_xxxxx.json</pre>
                </li>
                <li>
                    <strong>Copy updated <code>.env</code> into Docker container</strong>
                    <pre class="bg-gray-100 p-2 rounded text-xs whitespace-pre-wrap">docker cp .env googlesheet-laravel-app:/var/www/html/.env</pre>
                </li>
                <li>
                    <strong>Clear Laravel config cache inside the container</strong>
                    <pre class="bg-gray-100 p-2 rounded text-xs whitespace-pre-wrap">docker compose exec app php artisan config:clear</pre>
                </li>
            </ol>
        </div>
    </div>
@endsection
