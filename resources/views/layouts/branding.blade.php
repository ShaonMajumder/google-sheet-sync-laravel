<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Google Sheet User Authorization')</title>
    <script src="https://cdn.tailwindcss.com"></script>
    @yield('scripts')
</head>
<body class="flex items-center justify-center min-h-screen bg-gray-100">
    <div class="bg-white p-6 rounded-2xl shadow-lg w-96 text-center">
        <h1 class="text-4xl font-bold text-gray-800">Google Sheet Sync Laravel</h1>
        <p class="text-lg text-gray-600 mt-2">Easily sync your data with Google Sheets.</p>

        @yield('content')
    </div>
</body>
</html>
