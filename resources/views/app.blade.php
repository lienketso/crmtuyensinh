<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'AI Tuyển Sinh') }}</title>
    @php
        $faviconPath = config('app.crm_favicon');
        $faviconUrl = $faviconPath
            ? asset('storage/' . ltrim($faviconPath, '/'))
            : asset('favicon.ico');
    @endphp
    <link rel="icon" href="{{ $faviconUrl }}" type="image/x-icon">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <script>
        // Pass base path to Vue app
        window.APP_BASE_PATH = '{{ rtrim(config('app.url'), '/') }}';
    </script>
</head>
<body>
    <div id="app"></div>
</body>
</html>
