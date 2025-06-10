<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>@yield('title', 'My Site')</title>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <!-- Plugins CSS -->
    <link rel="stylesheet" href="{{ $site }}/assets/css/plugins.css">
    <!-- Icon Plugins CSS -->
    <link rel="stylesheet" href="{{ $site }}/assets/css/iconplugins.css">
    <!-- Style CSS -->
    <link rel="stylesheet" href="{{ $site }}/assets/css/style.css">
    <!-- Responsive CSS -->
    <link rel="stylesheet" href="{{ $site }}/assets/css/responsive.css">
    {{--<link rel="stylesheet" href="{{ asset('your-theme/css/style.css') }}">--}}
    @stack('styles')
</head>
<body>
@include('partials.header')

<div class="class-details-area pt-100 pb-70">
    <div class="container">
        @yield('content')
    </div>
</div>

@include('partials.footer')

{{--<script src="{{ asset('your-theme/js/script.js') }}"></script>--}}
@stack('scripts')
</body>
</html>
