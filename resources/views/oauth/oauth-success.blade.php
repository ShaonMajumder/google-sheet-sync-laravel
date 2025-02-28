@extends('layouts.authorization')

@section('title', 'OAuth Callback')

@section('scripts')
    <script>
        let countdown = 5;
        function updateCountdown() {
            if (countdown === 0) {
                window.location.href = "{{ $redirectUrl ?? '' }}";
            } else {
                document.getElementById('countdown').innerText = countdown;
                countdown--;
                setTimeout(updateCountdown, 1000);
            }
        }
        window.onload = updateCountdown;
    </script>
@endsection

@section('content')
    <div class="mt-6 space-y-4">
        @if($success)
            <div class="bg-green-100 text-green-800 border-l-4 border-green-500 p-4 rounded-lg">
                <h2 class="text-xl font-semibold">Authorization Successful</h2>
                <p>{{ $message }}</p>
                <p class="mt-2 text-sm">You will be redirected in <span id="countdown" class="font-bold text-lg">5</span> seconds...</p>
            </div>
        @else
            <div class="bg-red-100 text-red-800 border-l-4 border-red-500 p-4 rounded-lg">
                <h2 class="text-xl font-semibold">Authorization Failed</h2>
                <p>{{ $message }}</p>
            </div>
        @endif
    </div>
@endsection
