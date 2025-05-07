<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Google Sheet User Authorization')</title>
    <script src="https://cdn.tailwindcss.com"></script>

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>

    <script>
        window.onload = function () {
            toastr.options = {
                "closeButton": true,
                "newestOnTop": false,
                "progressBar": true,
                "positionClass": "toast-bottom-center",
                "preventDuplicates": false,
                "onclick": null,
                "showDuration": "300",
                "hideDuration": "1000",
                "timeOut": "5000",
                "extendedTimeOut": "1000",
                "showEasing": "swing",
                "hideEasing": "linear",
                "showMethod": "fadeIn",
                "hideMethod": "fadeOut"
            };
        };
    </script>
    @yield('scripts')
</head>
<body class="flex items-center justify-center min-h-screen bg-gray-100">
    <div id="toast-container" class="toast-top-right"></div>
    <div class="bg-white p-6 rounded-2xl shadow-lg w-96 text-center">
        <h1 class="text-4xl font-bold text-gray-800">Google Sheet Sync Laravel</h1>
        <p class="text-lg text-gray-600 mt-2">Easily sync your data with Google Sheets.</p>

        @yield('content')
        
        <footer class="text-center text-sm text-gray-400 mt-8">
            &copy; 2024-{{ date('Y') }} Google Sheet Sync Laravel by Shaon Majumder
        </footer>
    </div>
</body>
</html>
