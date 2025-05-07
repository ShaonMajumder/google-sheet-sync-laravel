@extends('layouts.branding')

@section('title', 'Google Sheet Sync')

@section('content')
    @php
        $route = empty($tokenData) ? route('get.access') : route('revoke.access');
        $buttonClass = empty($tokenData) ? 'bg-blue-500 hover:bg-blue-600' : 'bg-red-500 hover:bg-red-600';
        $buttonText = empty($tokenData) ? 'Get Access' : 'Revoke Access';
    @endphp

    <div class="mt-6 space-y-6">
        <div class="text-center">

            <span class="text-sm text-gray-500">Current Version: <strong>v1.2.0</strong></span>
        </div>

        <div class="text-center">
            @if ($needsSetup)
                <div class="text-center mt-6">
                    <a href="{{ route('setup.show') }}" class="bg-blue-600 text-white px-4 py-2 rounded">Go to Setup Page</a>
                </div>
            @else
                <p>App is ready!</p>
                <a href="{{ $route }}" class="inline-block {{ $buttonClass }} text-white font-bold py-2 px-6 rounded-lg">
                    {{ $buttonText }}
                </a>
                <a href="{{ url('api/documentation') }}" target="_blank" class="inline-block bg-green-500 hover:bg-green-600 text-white font-bold py-2 px-6 rounded-lg">
                    Use API Right Now
                </a>
            @endif
        </div>

        <div class="bg-white shadow-md rounded-lg p-6">
            <h2 class="text-2xl font-semibold mb-3">🚀 Features</h2>
            <ul class="list-disc list-inside space-y-1 text-gray-700 text-left">
                <li>Sync your Laravel app with Google Sheets effortlessly</li>
                <li>Create new Google Spreadsheets</li>
                <li>Add sheets to existing spreadsheets</li>
                <li>Insert, read, and append data to sheets</li>
                <li>OAuth 2.0 Integration</li>
                <li>Token management using Redis</li>
            </ul>
        </div>

        <div class="bg-white shadow-md rounded-lg p-6">
            <h2 class="text-2xl font-semibold mb-3">⚙️ User Guide</h2>
            <p class="text-gray-700 mb-2">To get started, follow the instructions in the <code>README.md</code>:</p>
            <ol class="list-decimal list-inside text-gray-700 space-y-1 text-left">
                <li>Set up your Google Cloud project and enable Sheets + Drive APIs.  Navigate to <a href="https://console.cloud.google.com/apis/credentials" target="_blank" class="text-blue-600 underline">APIs & Services → Credentials</a></li>
                <li>Download <code>credentials.json</code></li>
                <li>Upload in <a href="{{ route('setup.show') }}" target="_blank" class="text-blue-600 underline">Setup page</a> or <a href="{{ route('setup.credentials.manual') }}" target="_blank" class="text-blue-600 underline">Follow Manual Guide</a></li>
                <li><a href="{{ route('get.access') }}" class="text-blue-600 underline">Get Access</a></li>
                <li>Get the api key</li>
                <li>Use <a href="{{ url('api/documentation') }}" target="_blank" class="text-blue-600 underline">Api Guide</a></li>
            </ol>
        </div>

        <!-- Kibana Log Viewing -->
        <div class="bg-white shadow-md rounded-lg p-6">
            <h2 class="text-2xl font-semibold mb-3">🔍 Log Viewing</h2>
            <p class="text-gray-700 mb-2">You can view application logs and monitor them on the Kibana dashboard:</p>
            <a href="http://localhost:5601/app/discover#/" target="_blank" class="text-blue-600 underline">Go to Kibana Dashboard</a>
        </div>

        <!-- Elasticsearch Index Deletion -->
        <div class="bg-white shadow-md rounded-lg p-6 mt-6">
            <h2 class="text-2xl font-semibold mb-3">⚠️ Elasticsearch Index Deletion</h2>
            <p class="text-gray-700 mb-2">
                You can delete the current Elasticsearch index using the form below.
                <br>
                <span class="text-red-600 font-semibold">Be cautious as this action is irreversible.</span>
            </p>

            <div class="text-center mt-4">
                <a target="_blank" href="{{ route('elasticsearch.delete-index-form') }}" class="inline-block bg-red-500 hover:bg-red-600 text-white font-bold py-2 px-6 rounded-lg">
                    Delete Index
                </a>
            </div>
        </div>
    </div>
@endsection
