@extends('layouts.authorization')

@section('title', 'Revoke Access')

@section('scripts')
    <script>
        function revokeAccess() {
            console.log('Requesting revoke access...');
            fetch("{{ route('google-sheets.revoke.access') }}")
                .then(response => {
                    if (response.ok) {
                        console.log('Access revoked successfully');
                    } else {
                        console.error('Failed to revoke access');
                        alert("Failed to revoke access. Please try again.");
                    }
                    window.location.href = "{{ route('home') }}";
                })
                .catch(error => {
                    console.error('Error:', error);
                    window.location.href = "{{ route('home') }}";
                });
        }
    </script>
@endsection

@section('content')
    <div class="mt-6 space-y-4">
        <p class="text-gray-600">Are you sure you want to revoke your access?</p>
        
        <button onclick="revokeAccess()" class="w-full bg-red-500 hover:bg-red-600 text-white font-bold py-2 px-4 rounded-lg">
            Revoke Access
        </button>

        <p class="mt-4 text-sm text-gray-600">
            If you revoke access, you will no longer be able to sync your data with Google Sheets.
        </p>
    </div>
@endsection
