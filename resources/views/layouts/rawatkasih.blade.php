<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="color-scheme" content="light">
    <meta name="referrer" content="no-referrer">
    <title>@yield('title', 'RawatKasih')</title>
    {{-- Load the self-contained Figma styling first, even without npm/Vite. --}}
    <link rel="stylesheet" href="{{ asset('css/rawatkasih.css') }}?v={{ filemtime(public_path('css/rawatkasih.css')) }}">
    {{-- The optional bundled CSS provides local fonts and Flux/Tailwind assets. --}}
    @if (is_file(public_path('hot')) || is_file(public_path('build/manifest.json')))
        @vite('resources/css/app.css')
    @endif
    <script src="{{ asset('js/rawatkasih.js') }}" defer></script>
</head>
<body class="rk-body">
    <a class="rk-skip" href="#main">Lewati ke konten</a>
    @yield('content')
</body>
</html>
