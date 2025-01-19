<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>OAuth Callback</title>
    <script>
        // Countdown logic
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
        window.onload = updateCountdown; // Start countdown when page loads
    </script>
</head>
<body>
    <div>
        @if($success)
            <h1>Authorization Successful</h1>
            <p>{{ $message }}</p>
            <p>You will be redirected in <span id="countdown">5</span> seconds...</p>
        @else
            <h1>Authorization Failed</h1>
            <p>{{ $message }}</p>
        @endif
    </div>
</body>
</html>
