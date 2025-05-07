@extends('layouts.branding')

@section('title', 'Delete Elasticsearch Index')

@section('content')
    @php
        $route = route('delete-elasticsearch-index', ['index' => $index]);
    @endphp

    <div class="mt-6 space-y-6 max-w-4xl mx-auto">
        {{-- Page Heading --}}
        <div class="text-left border-b pb-4 mb-4">
            <h1 class="text-3xl font-bold text-gray-800">Delete Elasticsearch Index</h1>
            <p class="text-sm text-gray-500 mt-1">Safely delete an Elasticsearch index by name. This action is irreversible.</p>
        </div>

        {{-- Breadcrumb --}}
        <nav class="text-sm text-gray-600 mb-6" aria-label="Breadcrumb">
            <ol class="list-reset flex space-x-2">
                <li><a href="{{ url('/') }}" class="text-blue-600 hover:underline">Home</a></li>
                <li><span>/</span></li>
                <li><a href="{{ url('/elasticsearch/delete-index-form') }}" class="text-gray-800 font-medium">Delete Index</a></li>
            </ol>
        </nav>

        {{-- Response Box --}}
        @if (isset($message))
            <div class="mb-6 text-left">
                <h3 class="text-lg font-semibold text-gray-700 mb-2">Elasticsearch Response</h3>
                <div class="border rounded p-4 overflow-auto max-h-64 {{ $status == 'success' ? 'border-green-500 bg-green-50 text-green-700' : 'border-red-500 bg-red-50 text-red-700' }}">
                    <pre class="whitespace-pre-wrap break-words">{{ $message }}</pre>
                </div>
            </div>
        @endif

        {{-- Delete Form --}}
        <form action="{{ $route }}" method="POST" class="max-w-xl mx-auto text-center">
            @csrf
            @method('POST')

            <div class="mb-4">
                <label for="index" class="block text-sm font-medium text-gray-700 mb-1">Index Name:</label>
                <input type="text" id="index" name="index" value="{{ old('index', $index) }}" class="w-full border rounded p-2 text-center focus:outline-none focus:ring-2 focus:ring-blue-400">
            </div>

            <button type="submit" class="bg-red-500 hover:bg-red-600 text-white px-4 py-2 rounded-lg">
                Delete Index
            </button>
        </form>

        <p class="mt-4 text-center text-sm text-gray-600">
            ⚠️ Be cautious. Deleting the index will permanently remove its data.
        </p>
    </div>
@endsection
