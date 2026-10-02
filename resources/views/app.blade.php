<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title inertia>{{ config('app.name') }}</title>
    <style id="school-theme">
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
