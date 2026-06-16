{{--
    Каноничен базов layout за auth страници (login, регистрация, OTP и т.н.).
    Същата идея като layout.default, но БЕЗ header/навигация — чисто платно,
    центрирано вертикално. Темите могат да го override-нат през
    themes/{theme}/layout/auth.blade.php при нужда.
--}}
<!DOCTYPE html>
<html lang="{{$site_language}}" dir="{{$text_direction}}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Engine !')</title>

    {{-- Bootstrap 5 --}}
    @if($text_direction=='rtl')
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    @else
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    @endif
    {{-- Bootstrap Icons --}}
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">

    <style>
        html, body {
            height: 100%;

        }
        body {
            display: flex;
            flex-direction: column;
        }
        main {
            flex: 1 0 auto;
            display: flex;
            align-items: center;
            justify-content: center;
        }
    </style>
    @stack('styles')
</head>
<body>
<main>
@yield('content')
</main>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
@stack('scripts')
</body>
</html>
