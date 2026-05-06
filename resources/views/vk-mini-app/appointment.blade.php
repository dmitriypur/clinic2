<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta name="robots" content="noindex, nofollow">
    <title>{{ config('app.name') }} — онлайн-запись</title>
    <meta name="theme-color" content="#f5841f">

    @vite(['resources/css/app.css', 'resources/js/vk-mini-app-appointment.js'])

    <script>
        window.config = @json(Clinic::scriptVariables());
    </script>
</head>
<body class="bg-white antialiased text-interactive [&_*]:[-webkit-tap-highlight-color]:transparent">
    <div id="vk-appointment-app"></div>
</body>
</html>
