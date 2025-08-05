<!doctype html>
<html lang="bg">
@include('partials.header')
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

@include('partials.footer')
</body>
</html>
