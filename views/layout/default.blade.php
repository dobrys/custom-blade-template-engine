<!doctype html>
<html lang="bg">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Заглавие на страницата')</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
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
</head>
<body>
{{-- Хедър --}}
<header class="bg-primary text-white p-3">
    <div class="container">
        <h1 class="h4 mb-0">Заглавие на сайта</h1>
    </div>
</header>

{{-- Основно съдържание --}}
<main class="container my-4">
    @yield('content')
</main>

{{-- Футър (sticky) --}}
<footer class="bg-dark text-white text-center py-3 mt-auto">
    <div class="container">
        <small>&copy; {{ date('Y') }} Моето лого или име</small>
    </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
@stack('scripts')
</body>
</html>
