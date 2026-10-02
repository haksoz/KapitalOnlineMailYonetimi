<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="utf-8">
    <title>@yield('title', 'Belge')</title>
    <style>
        body { margin: 0; font-family: DejaVu Sans, Arial, sans-serif; font-size: 11px; line-height: 1.45; color: #111827; }
        table { width: 100%; border-collapse: collapse; }
        h1, h2, p { margin: 0; }
        @yield('extra-css')
    </style>
</head>
<body>
    @yield('content')
</body>
</html>
