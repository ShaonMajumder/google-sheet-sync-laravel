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
                        window.location.href = "{{ url('/') }}"; // change this route if needed
                    }
                }, 1000);
            </script>
        @else
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
