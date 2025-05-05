@extends('layouts.branding')

@section('title', 'API Guide - Google Sheet Sync')

@section('content')
    <div class="mt-6 space-y-6">
        <div class="text-center">
            <span class="text-sm text-gray-500">Current Version: <strong>v1.2.0</strong></span>
        </div>

        <div class="bg-white shadow-md rounded-lg p-6">
            <h2 class="text-2xl font-semibold mb-3">🛠 API Guide</h2>
            <p class="text-gray-700 mb-2">Below are the available API routes for integrating with Google Sheets:</p>

            <!-- Table with horizontal scroll handling -->
            <div class="overflow-x-auto max-w-full">
                <table class="min-w-full bg-white border border-gray-200">
                    <thead>
                        <tr>
                            <th class="py-2 px-4 border-b">Method</th>
                            <th class="py-2 px-4 border-b">Endpoint</th>
                            <th class="py-2 px-4 border-b">Description</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td class="py-2 px-4 border-b">GET</td>
                            <td class="py-2 px-4 border-b">{{ url('/google-sheets/api/v0/access-revoke') }}</td>
                            <td class="py-2 px-4 border-b">Revoke access to Google Sheets</td>
                        </tr>
                        <tr>
                            <td class="py-2 px-4 border-b">POST</td>
                            <td class="py-2 px-4 border-b">{{ url('/google-sheets/api/v0/create-spreadsheet') }}</td>
                            <td class="py-2 px-4 border-b">Create a new Google Spreadsheet</td>
                        </tr>
                        <tr>
                            <td class="py-2 px-4 border-b">GET</td>
                            <td class="py-2 px-4 border-b">{{ url('/google-sheets/api/v0/delete-sheet') }}</td>
                            <td class="py-2 px-4 border-b">Delete a spreadsheet by title</td>
                        </tr>
                        <tr>
                            <td class="py-2 px-4 border-b">POST</td>
                            <td class="py-2 px-4 border-b">{{ url('/google-sheets/api/v0/create-sheet') }}</td>
                            <td class="py-2 px-4 border-b">Create a sheet in a spreadsheet</td>
                        </tr>
                        <tr>
                            <td class="py-2 px-4 border-b">GET</td>
                            <td class="py-2 px-4 border-b">{{ url('/google-sheets/api/v0/delete-sheet/{spreadsheetId}/{sheetName}') }}</td>
                            <td class="py-2 px-4 border-b">Delete a specific sheet</td>
                        </tr>
                        <tr>
                            <td class="py-2 px-4 border-b">GET</td>
                            <td class="py-2 px-4 border-b">{{ url('/google-sheets/api/v0/read-sheet') }}</td>
                            <td class="py-2 px-4 border-b">Read data from a sheet</td>
                        </tr>
                        <tr>
                            <td class="py-2 px-4 border-b">POST</td>
                            <td class="py-2 px-4 border-b">{{ url('/google-sheets/api/v0/insert-data/{spreadsheetId}/{sheetName}') }}</td>
                            <td class="py-2 px-4 border-b">Insert data into a specific sheet</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <footer class="text-center text-sm text-gray-400 mt-8">
            &copy; 2024-{{ date('Y') }} Google Sheet Sync Laravel by Shaon Majumder
        </footer>
    </div>
@endsection
