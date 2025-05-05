@extends('layouts.authorization')

@section('title', 'Google Sheet Setup')

@section('content')
    <div class="mt-6 space-y-4">
        @if(session('success'))
            <div class="bg-green-200 text-green-800 p-4 rounded-lg">
                {{ session('success') }}
                <p class="mt-2 text-sm text-gray-600">
                    Redirecting to the app page in <span id="countdown">3</span> seconds...
                </p>
            </div>

            <script>
                let seconds = 3;
                const countdown = document.getElementById('countdown');

                const interval = setInterval(() => {
                    seconds--;
                    countdown.textContent = seconds;

                    if (seconds <= 0) {
                        clearInterval(interval);
                        window.location.href = "{{ url('/') }}";
                    }
                }, 1000);
            </script>
        @else
            <p class="text-sm text-red-600 font-medium mb-2">
                ⚠️ No Google OAuth credentials found in the system. Please follow the steps below to upload one.
            </p>
            <div class="p-4 mb-4 bg-blue-50 border border-blue-200 rounded-lg text-sm text-blue-900 break-words overflow-auto">
                <strong>🔐 Google OAuth Setup for Laravel Google Sheet Sync</strong>
                <ol class="list-decimal pl-5 mt-2 space-y-1">
                    <li>
                        Go to <a href="https://console.cloud.google.com/" target="_blank" class="text-blue-600 underline">Google Cloud Console</a>
                    </li>
                    <li>
                        Navigate to <a href="https://console.cloud.google.com/apis/credentials" target="_blank" class="text-blue-600 underline">APIs & Services → Credentials</a>
                    </li>
                    <li>
                        Download your <strong>OAuth 2.0 Credentials JSON</strong> from <em>OAuth 2.0 Client IDs</em>
                    </li>
                    <li>
                        Upload the downloaded file below.
                    </li>
                    <li>
                        Clear Laravel config cache inside Docker:
                        <pre class="bg-gray-100 p-2 rounded mt-1 text-xs">docker compose exec app php artisan config:clear</pre>
                    </li>
                </ol>
            </div>

            <form method="POST" action="{{ route('setup.credentials') }}" enctype="multipart/form-data">
                @csrf
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700">Upload Credentials JSON</label>
                    <input type="file" name="credentials_file" accept=".json"
                           class="mt-1 block w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring focus:ring-blue-200" required>
                </div>

                <button type="submit"
                        class="w-full bg-blue-500 hover:bg-blue-600 text-white font-bold py-2 px-4 rounded-lg">
                    Save Credentials
                </button>
            </form>
        @endif
    </div>
@endsection
