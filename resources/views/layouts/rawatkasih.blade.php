<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="color-scheme" content="light">
    <meta name="referrer" content="no-referrer">
    <title>@yield('title', 'RawatKasih')</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="rk-body">
    <a class="rk-skip" href="#main">Lewati ke konten</a>
    @yield('content')
</body>
</html>
