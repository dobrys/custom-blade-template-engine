<!DOCTYPE html>
<html lang="{{$site_language}}" dir="{{$text_direction}}">

<head>
    <meta charset="utf-8">
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
        }
        footer {
            flex-shrink: 0;
        }
    </style>
    @stack('styles')
</head>
<body>
{{----}}@dump($site_language,$text_direction)
@include('partials.header')
<main>
@yield('content')
</main>
@include('partials.footer')
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
@stack('scripts')
</body>
</html>
