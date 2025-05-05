@extends('layouts.authorization')

@section('title', 'Revoke Access')

@section('scripts')
    @if(session('success'))
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                let seconds = 3;
                const countdownEl = document.getElementById('countdown');

                const interval = setInterval(function () {
                    seconds--;
                    if (countdownEl) {
                        countdownEl.textContent = seconds;
                    }

                    if (seconds <= 0) {
                        clearInterval(interval);
                        window.location.href = "{{ route('landing.page') }}";
                    }
                }, 1000);
            });
        </script>
    @endif
@endsection

@section('content')
    <div class="mt-6 space-y-4">
        {{-- Success Message --}}
        @if(session('success'))
            <div class="p-4 bg-green-100 text-green-800 rounded-lg">
                {{ session('success') }}
                <br>
                Redirecting to homepage in <span id="countdown">3</span> seconds...
            </div>
        @endif

        {{-- Error Message --}}
        @if(session('error'))
            <div class="p-4 bg-red-100 text-red-800 rounded-lg">
                {{ session('error') }}
            </div>
        @endif

        {{-- Confirmation Prompt --}}
        @if(!session('success'))
            <p class="text-gray-600">Are you sure you want to revoke your access?</p>

            <form method="POST" action="{{ route('revoke.access.delete') }}">
                @csrf
                <button type="submit" class="w-full bg-red-500 hover:bg-red-600 text-white font-bold py-2 px-4 rounded-lg">
                    Revoke Access
                </button>
            </form>

            <p class="mt-4 text-sm text-gray-600">
                If you revoke access, you will no longer be able to sync your data with Google Sheets.
            </p>
        @endif
    </div>
@endsection
