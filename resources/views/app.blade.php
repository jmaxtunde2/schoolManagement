<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <title inertia>{{ config('app.name') }}</title>

    {{-- PWA --}}
    <link rel="manifest" href="/manifest.webmanifest">
    <meta name="theme-color" content="#059669">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="CoriSchool">
    <link rel="icon" type="image/png" href="/icons/favicon-32.png">
    <link rel="apple-touch-icon" href="/icons/apple-touch-icon-180.png">
    <style id="school-theme" nonce="{{ $csp_nonce }}">
        :root {
            --school-primary: {{ $branding['primary_color'] }};
            --school-primary-contrast: {{ $branding['primary_contrast'] }};
            --school-secondary: {{ $branding['secondary_color'] }};
            --school-accent: {{ $branding['accent_color'] }};
        }
    </style>
    @routes
    @viteReactRefresh
    @vite(['resources/css/app.css', 'resources/js/app.jsx'])
    @inertiaHead
</head>
<body class="bg-slate-50 text-slate-900 antialiased">
    @inertia
</body>
</html>
