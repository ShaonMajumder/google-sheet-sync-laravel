@extends('layouts.authorization')

@section('title', 'Google Sheet Sync')

@section('content')
    @php
        $route = empty($tokenData) ? route('get.access') : route('revoke.access');
        $buttonClass = empty($tokenData) ? 'bg-blue-500 hover:bg-blue-600' : 'bg-red-500 hover:bg-red-600';
        $buttonText = empty($tokenData) ? 'Get Access' : 'Revoke Access';
    @endphp
    <div class="mt-6 space-y-4">
        <a href="{{ $route }}" class="w-full block {{ $buttonClass }} text-white font-bold py-2 px-4 rounded-lg">
            {{ $buttonText }}
        </a>
    </div>
@endsection
