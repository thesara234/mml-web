<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#0b2e3f">
    <title>@hasSection('title')@yield('title') | @endif{{ config('mml.brand') }}</title>
    <link rel="icon" href="{{ asset('favicon.svg') }}" type="image/svg+xml">
    <link rel="preload" href="{{ asset('vendor/fonts/plus-jakarta-sans-latin-wght-normal.woff2') }}" as="font" type="font/woff2" crossorigin>
    <link rel="stylesheet" href="{{ asset('vendor/lucide/lucide.css') }}">
    <link rel="stylesheet" href="{{ asset('css/mml.css') }}?v=1">
    @stack('head')
    <script src="{{ asset('js/mml.js') }}?v=1" defer></script>
    <script src="{{ asset('vendor/alpine/alpine.min.js') }}" defer></script>
</head>
<body class="@yield('body_class')">
@yield('body')
@stack('scripts')
<script>
    // If Alpine.js didn't load (e.g. public/vendor is missing), say so instead of showing a blank screen.
    window.addEventListener('load', function () {
        if (window.Alpine) return;
        var d = document.createElement('div');
        d.className = 'alert alert-error';
        d.style.cssText = 'position:fixed;left:16px;right:16px;bottom:16px;z-index:999';
        d.textContent = 'Part of this page could not load (public/vendor/alpine/alpine.min.js). Interactive screens such as Send money need it. Make sure the mml-web/public/vendor folder from the project zip is present, then refresh.';
        document.body.appendChild(d);
    });
</script>
</body>
</html>
